<?php


namespace Hugo\Data\Controller;


class Constants {

    const CONTROLLER_NS = '\\Hugo\\Data\\Controller';
    const CONTENT_TYPE = 'application/json;charset=utf-8';

    // 2xx response codes - request OK
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NO_CONTENT = 204;

    // 3xx response codes - redirects
    const HTTP_MOVED = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;

    // 4xx response codes - client errors
    const HTTP_BAD_REQ = 400;
    const HTTP_UNAUTHORISED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_BAD_METHOD = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_TIMEOUT = 408;
    const HTTP_GONE = 410;
    const HTTP_UNSUPPORTED_MEDIA = 415;
    const HTTP_TEAPOT = 418;

    // 5xx response codes - server errors
    const HTTP_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;

} 