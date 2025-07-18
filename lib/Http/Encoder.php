<?php

namespace CHRobinson\Http;

use CHRobinson\Http\Serializer\Form;
use CHRobinson\Http\Serializer\Json;
use CHRobinson\Http\Serializer\Multipart;
use CHRobinson\Http\Serializer\Text;
use CHRobinson\Http\Serializer\Passthrough;

class Encoder
{
    private $serializers = [];

    function __construct()
    {
        $this->serializers[] = new Json();
        $this->serializers[] = new Text();
        $this->serializers[] = new Multipart();
        $this->serializers[] = new Form();
        $this->serializers[] = new Passthrough(); // Register PDF passthrough
    }

    public function serializeRequest(HttpRequest $request)
    {
        if (!array_key_exists('content-type', $request->headers)) {
            $message = "HttpRequest does not have Content-Type header set";
            echo $message;
            throw new \Exception($message);
        }

        $contentType = $request->headers['content-type'];
        /** @var Serializer $serializer */
        $serializer = $this->serializer($contentType);

        if (is_null($serializer)) {
            $message = sprintf("Unable to serialize request with Content-Type: %s. Supported encodings are: %s", $contentType, implode(", ", $this->supportedEncodings()));
            echo $message;
            throw new \Exception($message);
        }

        if (!(is_string($request->body) || is_array($request->body))) {
            $message = "Body must be either string or array";
            echo $message;
            throw new \Exception($message);
        }

        $serialized = $serializer->encode($request);

        if (array_key_exists("content-encoding", $request->headers) && $request->headers["content-encoding"] === "gzip") {
            $serialized = gzencode($serialized);
        }
        return $serialized;
    }

    public function deserializeResponse($responseBody, $headers)
    {

        if (!array_key_exists('content-type', $headers)) {
            $message = "HTTP response does not have Content-Type header set";
            echo $message;
            throw new \Exception($message);
        }

        $contentType = $headers['content-type'];
        /** @var Serializer $serializer */
        $serializer = $this->serializer($contentType);

        if (is_null($serializer)) {
            throw new \Exception(sprintf("Unable to deserialize response with Content-Type: %s. Supported encodings are: %s", $contentType, implode(", ", $this->supportedEncodings())));
        }

        if (array_key_exists("content-encoding", $headers) && $headers["content-encoding"] === "gzip") {
            $responseBody = gzdecode($responseBody);
        }

        return $serializer->decode($responseBody);
    }

    private function serializer($contentType)
    {
        /** @var Serializer $serializer */
        foreach ($this->serializers as $serializer) {
            try {
                if (preg_match($serializer->contentType(), $contentType) == 1) {
                    return $serializer;
                }
            } catch (\Exception $ex) {
                $message = sprintf("Error while checking content type of %s: %s", get_class($serializer), $ex->getMessage());
                echo $message;
                throw new \Exception($message, $ex->getCode(), $ex);
            }
        }

        return NULL;
    }

    private function supportedEncodings()
    {
        $values = [];
        /** @var Serializer $serializer */
        foreach ($this->serializers as $serializer) {
            $values[] = $serializer->contentType();
        }
        return $values;
    }
}
