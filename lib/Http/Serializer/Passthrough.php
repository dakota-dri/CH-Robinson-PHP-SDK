<?php
namespace CHRobinson\Http\Serializer;

use CHRobinson\Http\Serializer; // Correct import for the interface

class Passthrough implements Serializer
{
    public function contentType()
    {
        return '/^application\/pdf$/';
    }

    public function encode($request)
    {
        return $request->body;
    }

    public function decode($responseBody)
    {
        return $responseBody; // Just return raw data
    }
} 