<?php

namespace Drutiny\BlazeMeter\Audit;

use Drutiny\Annotation\Param;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Credential\Manager;
use Drutiny\Acquia\CloudApiDrushAdaptor;
use Drutiny\Acquia\AcquiaTargetInterface;
use Drutiny\AuditValidationException;

/**
 * Ensure an environment has custom domains set.
 * @Param(
 *  name = "report-type",
 *  description = "one of summary, aggregatereport, errorsreport",
 *  type = "array",
 *  default = {"summary"}
 * )
 */
class BlazeMeter extends AbstractAnalysis {

  protected function requireApiCredentials() {
    return Manager::load('blazemeter') ? TRUE : FALSE;
  }

  protected function api() {
    $creds = Manager::load('blazemeter');
    return new Client($creds['key'], $creds['secret']);
  }

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $uri = $sandbox->getTarget()->uri();
    $host = strpos($uri, 'http') === 0 ? parse_url($uri, PHP_URL_HOST) : $uri;
    $sandbox->setParameter('host', $host);
    $report_type = $sandbox->getParameter('report-type');

    if (!is_array($report_type)) {
      throw new AuditValidationException("Report Type parameter must be an array. " . ucwords(gettype($report_type)) . ' given.');
    }

    $options = [
      'names[]' => 'Apdex',
      'summarize' => TRUE,
      'from' => $sandbox->getReportingPeriodStart()->format(\DateTime::RFC3339),
      'to' => $sandbox->getReportingPeriodEnd()->format(\DateTime::RFC3339),
    ];

    $query = http_build_query($options);
    try {
      $response = $this->api()->request("GET", 'metrics/data.json?' . $query, $options);
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }

    if ($response) {
      $apdex_values = $response['metric_data']['metrics'][0]['timeslices'][0]['values'];
      $sandbox->setParameter('apdex_score', $apdex_values['score']);
      $sandbox->setParameter('apdex_threshold', $apdex_values['threshold']);
    }
  }

}
