<?php

namespace Drutiny\BlazeMeter\Audit;

use Drutiny\BlazeMeter\Client;
use Drutiny\Credential\Manager;
use Drutiny\Container;
use Drutiny\AuditResponse\AuditResponseException;

trait ApiEnabledAuditTrait {

  public function requireApiCredentials()
  {
    return Manager::load('blazemeter') ? TRUE : FALSE;
  }

  protected function api()
  {
    $creds = Manager::load('blazemeter');
    return new Client($creds['key'], $creds['secret']);
  }

  protected function getLatestMaster($workspace_id, $account_id, $from, $to)
  {
    $projects = $this->getProjects($workspace_id, $account_id);
    if (!empty($projects)) {
      $project = reset($projects);
      $masters = $this->getMasters($workspace_id, $project['id'], $from, $to);
      if (!empty($masters)) {
        return reset($masters);
      }
    }
    return FALSE;
  }

  protected function getWorkspaces($account_id)
  {
    try {
      $query = http_build_query(['accountId' => $account_id]);
      $response = $this->api()->request("GET", 'workspaces?' . $query);
      return $response['result'];
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }
    return FALSE;
  }

  protected function getProjects($workspace_id, $account_id)
  {
    try {
      $query = http_build_query(['accountId' => $account_id, 'workspaceId' => $workspace_id]);
      $response = $this->api()->request("GET", 'projects?' . $query);
      return $response['result'];
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }
    return FALSE;
  }

  protected function getMasters($workspace_id, $project_id, $from, $to)
  {
    try {
      $params = [
        'workspaceId' => $workspace_id,
        'projectId' => $project_id,
        'startTime' => $from,
        'endTime' => $to,
      ];
      $query = http_build_query($params);
      $response = $this->api()->request("GET", 'masters?' . $query);
      return $response['result'];
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }
    return FALSE;
  }

  public function parseResult($type, &$result, $sandbox)
  {
    switch ($type) {
      case 'summary':
        $summary = &$result['summary'][0];
        $summary['hits_avg'] = round($summary['hits_avg'], 2);
        $summary['avg'] = round($summary['avg'], 2);
        break;

      case 'aggregatereport':
        $sort_type = $sandbox->getParameter('sort');
        if (!empty($sort_type)) {
          $sort_type = $sort_type[0];
          $sort_column = array_column($result, $sort_type);
          array_multisort($sort_column, SORT_DESC, $result);
          foreach ($result as &$item) {
            $item['avgResponseTime'] = round($item['avgResponseTime'], 2);
          }
        }
        break;

      case 'errorsreport':
        foreach ($result as &$item) {
          if (!empty($item['errors'])) {
            foreach ($item['errors'] as &$value) {
              $value['rc'] = !empty($value['rc']) ? $value['rc'] : "NA";
              $value['rm'] = !empty($value['rm']) ? $value['rm'] : "NA";
              $value['count'] = !empty($value['count']) ? $value['count'] : "NA";
            }
          }
        }
        break;
    }
  }

}

?>
