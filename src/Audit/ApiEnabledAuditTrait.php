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

  protected function getLatestMaster($workspace_id, $account_id)
  {
    $projects = $this->getProjects($workspace_id, $account_id);
    if (!empty($projects)) {
      $project = reset($projects);
      $masters = $this->getMasters($workspace_id, $project['id']);
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

  protected function getMasters($workspace_id, $project_id)
  {
    try {
      $query = http_build_query(['workspaceId' => $workspace_id, 'projectId' => $project_id]);
      $response = $this->api()->request("GET", 'masters?' . $query);
      return $response['result'];
    }
    catch (\Exception $exception) {
      throw new AuditResponseException($exception->getMessage());
    }
    return FALSE;
  }

}

?>
