<?php
/**
 * ReportController.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Model\Client,
    Hugo\Data\Model\Report,
    Hugo\Data\OAuth\AuthServer,
    Hugo\Data\Storage\DB\MySQL,
    Symfony\Component\HttpFoundation\Response,
    Hugo\Data\Exception\InvalidRequestException;

/**
 * Class ReportController
 * @package Hugo\Data\Controller
 */
class ReportController extends AbstractController {

    /**
     * GET /report/{id}
     * Returns either a specific report or a list of reports
     *
     * @param null $id
     * @return Response
     */
    public function getIndex($id = null)
    {
        if(null === $id) {
            $response = Report::listArray(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));
        } else {
            $report = new Report(new MySQL(['db' => 'hugo_reports', 'table' => $id]));
            $reportArray = $report->toArray();

            // get the client id so we can return an array of the client details instead of the id
            $clientId = $reportArray['client_id'];
            unset($reportArray['client_id']);
            $client = new Client(new MySQL(['db' => 'hugo_reports', 'table' => 'clients']), $clientId);
            $reportArray['client'] = $client->toArray();

            $response = $reportArray + ['report-data' => $report->getReportDataArray()];
        }

        return new Response(json_encode($response, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * POST /report/
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @todo Find a better way to test the file type
     */
    public function postIndex()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $file = $this->request->files->get('csv');
        $csv = $file->move('/media/vagrant/www/api.hugowolferton.co.uk/uploads/', $file->getClientOriginalName());

        if($csv->getExtension() != "csv") {
            $this->log->error("Attempted to create report with {file} type", ['file' => $csv->getExtension()]);
            throw new InvalidRequestException("Uploaded file must be a CSV file.", Constants::HTTP_UNSUPPORTED_MEDIA);
        }

        $report = new Report(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));
        $report->processFile(
            $csv->openFile(),   // sends an \SplFileObject to be processed
            [
                'id'           => $this->request->get('id'),
                'client_id'    => $this->request->get('client_id'),
                'report_about' => $this->request->get('report_about')
            ]
        );

        return new Response(json_encode($report->toArray() + ['report-data' => $report->getReportDataArray()], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * PUT /report/{id}
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

        $report = new Report(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']), $id);

        // check to see if the report exists
        if(!$report->saved()) {
            $this->log->error("Attempted to update invalid report id {id}", ['id' => $id]);
            throw new InvalidRequestException("No report with ID {$id} found", Constants::HTTP_NOT_FOUND);
        }

        // check to see if a file was uploaded, if so update the report data
        $file = $this->request->files->get('csv');
        if((bool)$file) {
            $report->updateData($file->openFile());
        }

        // Check to see if the metadata fields have been sent in the request, otherwise use the current value
        $about = !is_null($this->request->request->get('report-about')) ? $this->request->request->get('report-about') : $report->report_about;
        $client = !is_null($this->request->request->get('client-id')) ? $this->request->request->get('client-id') : $report->client_id;
        $published = !is_null($this->request->request->get('published')) ? $this->request->request->get('published') : $report->published;
        $order = !is_null($this->request->request->get('report-order')) ? $this->request->request->get('report-order') : $report->order;

        if(!$report->checkValidOrder($order)) {
            $this->log->error("Invalid order sent with PUT /report/");
            throw new InvalidRequestException("Invalid order sent with request. Please ensure it is valid JSON and contains all columns", Constants::HTTP_BAD_REQ);
        }

        $report->set([
            'report_about'  => $about,
            'client_id'     => $client,
            'published'     => $published,
            'report_order'  => $order
        ]);

        if(!$report->save()) {
            $this->log->error("Error saving report {id}, check MySQL logs", ['id' => $id]);
            throw new \Exception("There was an error saving the report, please check logs", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode($report->toArray() + ['report-data' => $report->getReportDataArray()], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * DELETE /report/{id}
     *
     * @param $id
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function deleteIndex($id)
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $report = new Report(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']), $id);

        // check to see if the report exists
        if(!$report->saved()) {
            $this->log->error("Attempted to update invalid report id {id}", ['id' => $id]);
            throw new InvalidRequestException("No report with ID {$id} found", Constants::HTTP_NOT_FOUND);
        }

        if(!$report->delete()) {
            $this->log->error("Error deleting report {id}", ['id' => $id]);
            throw new \Exception("Error deleting report {$id}", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode(['success' => 'Report ' . $id . ' deleted'], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

}
