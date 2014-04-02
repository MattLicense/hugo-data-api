<?php
/**
 * ClientController.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Model\Client,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\OAuth\AuthServer,
    Symfony\Component\HttpFoundation\Response,
    Hugo\Data\Exception\InvalidRequestException;

class ClientController extends AbstractController {

    /**
     * GET /client/{id}
     *
     * @param null $id
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function getIndex($id = null)
    {
        $store = new MySQL(['db' => 'hugo_reports', 'table' => 'client']);

        if(null == $id) {
            $response = Client::listArray($store);
        } else {
            $client = new Client($store, $id);
            $response = $client->toArray();
        }

        return new Response(json_encode($response, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * POST /client/
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function postIndex()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $client = new Client(new MySQL(['db' => 'hugo_reports', 'table' => 'clients']));
        $client->processParameters($this->request->request);
        if(!$client->save()) {
            $this->log->error("Error saving new client, check MySQL logs");
            throw new \Exception("Error saving new client to database, check logs", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode($client->toArray(), JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * PUT /client/{id}
     *
     * @param $id
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function putIndex($id)
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $client = new Client(new MySQL(['db' => 'hugo_reports', 'table' => 'clients']), $id);
        $client->processParameters($this->request->request);
        if(!$client->save()) {
            $this->log->error("Error updating client id {id}, check MySQL logs", ['id' => $id]);
            throw new \Exception("Error updating client id {$id}, check logs", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode($client->toArray(), JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    public function deleteIndex($id)
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $client = new Client(new MySQL(['db' => 'hugo_reports', 'table' => 'clients']), $id);
        if(!$client->delete()) {
            $this->log->error("Error deleting client id {id}, check MySQL logs", ['id' => $id]);
            throw new \Exception("Error deleting client id {$id}, check logs", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode(['success' => 'Client ' . $id . ' deleted'], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

} 