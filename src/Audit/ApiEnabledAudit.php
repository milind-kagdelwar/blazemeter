<?php

namespace Drutiny\BlazeMeter\Audit;

use Drutiny\Annotation\Param;
use Drutiny\BlazeMeter\Audit\ApiEnabledAuditTrait;
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
 * @Param(
 *  name = "sort",
 *  description = "Sort by field",
 *  type = "string",
 *  default = "id"
 * )
 */
class ApiEnabledAudit extends AbstractAnalysis {
  use ApiEnabledAuditTrait;

  /**
   * @inheritdoc
   */
  public function gather(Sandbox $sandbox) {

    $uri = $sandbox->getTarget()->uri();
    $host = strpos($uri, 'http') === 0 ? parse_url($uri, PHP_URL_HOST) : $uri;
    $sandbox->setParameter('host', $host);
    $report_type = $sandbox->getParameter('report-type');
    $sort = $sandbox->getParameter('sort');

    if (!is_array($report_type)) {
      throw new AuditValidationException("Report Type parameter must be an array. " . ucwords(gettype($report_type)) . ' given.');
    }
    $report_type = reset($report_type);

    // Check if we have workspace id.
    $creds = Manager::load('blazemeter');
    $workspace_id = $creds['workspace_id'];
    if (empty($workspace_id)) {
      $workspace = $this->getWorkspaces($creds['account_id']);
      if ($workspace) {
        $workspace = reset($workspace);
        $workspace_id = $workspace['id'];
      }
    }

    // Get reporting period duration.
    $from = $sandbox->getReportingPeriodStart()->format('U');
    $to = $sandbox->getReportingPeriodEnd()->format('U');

    // Get master details, we will be fetching first master.
    $master = $this->getLatestMaster($workspace_id, $creds['account_id'], $from, $to);
    if (empty($master)) {
      throw new \Exception("There is no data available for this reportting period.");
      return;
    }
    $master = $master['id'];
    $format = 'data';
    if ($report_type === 'summary') {
      $format = 'summary';
    }

    $query = http_build_query(['from' => $from, 'to' => $to, 'sort[]' => $sort]);

    try {
      $response = $this->api()->request("GET", "masters/{$master}/reports/{$report_type}/{$format}?{$query}");
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }

    if ($response) {
      $this->parseResult($report_type, $response['result'], $sandbox);
      $sandbox->setParameter('count', count($response['result']));
      $sandbox->setParameter('result', $response['result']);
    }
  }

}
