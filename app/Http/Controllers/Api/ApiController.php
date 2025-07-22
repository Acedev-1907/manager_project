<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response as IlluminateResponse;

class ApiController extends Controller
{
    // Success (1xxx)
    const RESPONSE_OK          = 1000;
    const RESPONSE_CREATED     = 1001;
    const RESPONSE_UPDATED     = 1002;
    const RESPONSE_DELETED     = 1003;
    const RESPONSE_IN_PROGRESS = 1004;

    // Client errors (2xxx)
    const ERROR_VALIDATION     = 2000;
    const ERROR_NOT_FOUND      = 2001;
    const ERROR_DUPLICATE      = 2002;

    // Unexpected error (bug)
    const RESPONSE_SERVER_ERROR = 3000;  // eg: database connection error

    // Auth errors (4xxx)
    const ERROR_UNAUTHORIZED   = 4001;
    const ERROR_FORBIDDEN      = 4003;

    // Server errors (5xxx)
    const ERROR_INTERNAL       = 5000;

    /**
     * @var int
     */
    protected $statusCode = IlluminateResponse::HTTP_OK;

    /**
     * @var int
     */
    protected $returnCode = self::RESPONSE_OK;

    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * @param mixed $returnCode
     *
     * @return $this
     */
    public function setReturnCode($returnCode)
    {
        $this->returnCode = $returnCode;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondNotFound($message = 'Not found!')
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_NOT_FOUND)
            ->setReturnCode(self::ERROR_NOT_FOUND)
            ->respondWithError($message);
    }

    /**
     * @param string     $message
     * @param array|null $data    An optional associative array of data to be returned
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithError($message, array $data = null)
    {
        if ($this->getReturnCode() === self::RESPONSE_OK) {
            // this really should not happen as the
            // return code should be set when responding
            $this->setReturnCode(self::RESPONSE_SERVER_ERROR);
        }

        $payload = [
            'message' => $message,
            'status_code' => $this->getStatusCode(),
        ];

        if (is_array($data)) {
            $payload = array_merge($payload, $data);
        }

        return $this->respond([
            'error' => $payload,
        ]);
    }

    /**
     * @param       $data
     * @param array $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function respond($data, $headers = [])
    {
        $data['code'] = $this->getReturnCode();

        return response()->json($data, $this->getStatusCode(), $headers);
    }

    /**
     * @param $message
     * @param $newId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondCreated($message, $newId)
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_CREATED)
            ->setReturnCode(self::RESPONSE_CREATED)
            ->respond([
                'data' => [
                    'message' => $message,
                    'id' => $newId,
                ],
            ]);
    }

    /**
     * @param $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithMessage($message)
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_OK)
            ->setReturnCode(self::RESPONSE_OK)
            ->respond([
                'message' => $message,
            ]);
    }

    /**
     * @param $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithData($data)
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_OK)
            ->setReturnCode(self::RESPONSE_OK)
            ->respond([
                'data' => $data,
            ]);
    }

    /**
     * @param $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondUpdatedWithData($message = 'Updated', $data=null)
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_OK)
            ->setReturnCode(self::RESPONSE_UPDATED)
            ->respond([
                'message' => $message,
                'data' => $data
            ]);
    }

    /**
     * @param $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondUpdated($message = 'Updated')
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_OK)
            ->setReturnCode(self::RESPONSE_UPDATED)
            ->respond([
                'message' => $message,
            ]);
    }

    /**
     * @param $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondDeleted($message = 'Deleted')
    {
        return $this->setStatusCode(IlluminateResponse::HTTP_OK)
            ->setReturnCode(self::RESPONSE_DELETED)
            ->respond([
                'message' => $message,
            ]);
    }
}