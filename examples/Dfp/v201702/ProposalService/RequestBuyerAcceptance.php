<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Google\AdsApi\Examples\Dfp\v201702\ProposalService;

require '../../../../vendor/autoload.php';

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\DfpSession;
use Google\AdsApi\Dfp\DfpSessionBuilder;
use Google\AdsApi\Dfp\Util\v201702\StatementBuilder;
use Google\AdsApi\Dfp\v201702\ProposalService;
use Google\AdsApi\Dfp\v201702\RequestBuyerAcceptance as RequestBuyerAcceptanceAction;

/**
 * Requests buyer acceptance for proposals.
 *
 * This example is meant to be run from a command line (not as a webpage) and
 * requires that you've setup an `adsapi_php.ini` file in your home directory
 * with your API credentials and settings. See `README.md` for more info.
 */
class RequestBuyerAcceptance {

  const PROGRAMMATIC_PROPOSAL_ID = 'INSERT_PROGRAMMATIC_PROPOSAL_ID_HERE';

  public static function runExample(DfpServices $dfpServices,
      DfpSession $session, $programmaticProposalId) {
    $proposalService = $dfpServices->get($session, ProposalService::class);

    // Create a statement to select the proposals to request buyer acceptance
    // for.
    $pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
    $statementBuilder = (new StatementBuilder())
        ->where('id = :id')
        ->orderBy('id ASC')
        ->limit($pageSize)
        ->withBindVariableValue('id', $programmaticProposalId);

    // Retrieve a small amount of proposals at a time, paging through until all
    // proposals have been retrieved.
    $totalResultSetSize = 0;
    do {
      $page = $proposalService->getProposalsByStatement(
          $statementBuilder->toStatement());

      // Print out some information for the proposals to request buyer
      // acceptance for.
      if ($page->getResults() !== null) {
        $totalResultSetSize = $page->getTotalResultSetSize();
        $i = $page->getStartIndex();
        foreach ($page->getResults() as $proposal) {
          printf(
              "%d) Proposal with ID %d " .
                  "and name '%s' will be requested for buyer acceptance.\n",
              $i++,
              $proposal->getId(),
              $proposal->getName()
          );
        }
      }

      $statementBuilder->increaseOffsetBy($pageSize);
    } while ($statementBuilder->getOffset() < $totalResultSetSize);

    printf("Total number of proposals to request buyer acceptance for: %d\n",
        $totalResultSetSize);

    if ($totalResultSetSize > 0) {
      // Remove limit and offset from statement so we can reuse the statement.
      $statementBuilder->removeLimitAndOffset();

      // Create and perform action.
      $action = new RequestBuyerAcceptanceAction();
      $result = $proposalService->performProposalAction($action,
          $statementBuilder->toStatement());

      if ($result !== null && $result->getNumChanges() > 0) {
        printf("Number of proposals requested for buyer acceptance: %d\n",
            $result->getNumChanges());
      } else {
        printf("No proposals were requested for buyer acceptance.\n");
      }
    }
  }

  public static function main() {
    // Generate a refreshable OAuth2 credential for authentication.
    $oAuth2Credential = (new OAuth2TokenBuilder())
        ->fromFile()
        ->build();

    // Construct an API session configured from a properties file and the OAuth2
    // credentials above.
    $session = (new DfpSessionBuilder())
        ->fromFile()
        ->withOAuth2Credential($oAuth2Credential)
        ->build();

    self::runExample(
        new DfpServices(), $session, intval(self::PROGRAMMATIC_PROPOSAL_ID));
  }
}

RequestBuyerAcceptance::main();
