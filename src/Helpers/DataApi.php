<?php


namespace airmoi\FileMaker\Helpers;

use Exception;

class DataApi
{
    const ENDPOINT_BASE = '{host}/fmi/data/{version}';
    const ENDPOINT_DATABASES = '/databases';
    const ENDPOINT_INFOS = '/productInfo';
    const ENDPOINT_LAYOUTS = '/databases/{database}/layouts';
    const ENDPOINT_SCRIPTS = '/databases/{database}/scripts';
    const ENDPOINT_METADATA = '/databases/{database}/layouts/{layout}';
    const ENDPOINT_LOGIN = '/databases/{database}/sessions';
    const ENDPOINT_LOGOUT = '/databases/{database}/sessions/{sessionToken}';
    const ENDPOINT_FIND = '/databases/{database}/layouts/{layout}/_find';
    const ENDPOINT_RECORDS = '/databases/{database}/layouts/{layout}/records';
    const ENDPOINT_RECORD = '/databases/{database}/layouts/{layout}/records/{recordId}';
    const ENDPOINT_UPLOAD = '/databases/{database}/layouts/{layout}/records/{recordId}/containers/{containerFieldName}/{containerFieldRepetition}';
    const ENDPOINT_GLOBAL = '/databases/{database}/globals';
    const ENDPOINT_SCRIPT = '/databases/{database}/layouts/{layout}/script/{scriptName}';

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public static function prepareQuery(array $params)
    {
        $query = [
            'uri' => self::ENDPOINT_BASE,
            'queryParams' => [],
            'headers' => [],
            'method' => null,
            'body' => null,
            'params' => [
                'version' =>  'vLatest',
                'layout' =>  isset($params['-lay']) ? urlencode($params['-lay']) : null,
                'database' =>  isset($params['-db']) ? urlencode($params['-db']) : null,
                'recordId' =>  isset($params['-recid']) ? urlencode($params['-recid']) : null,
            ]
        ];

        if (array_key_exists('-recid', $params)
            && !array_key_exists('-dup', $params)
            && !array_key_exists('-edit', $params)
            && !array_key_exists('-delete', $params)
        ) {
            $query['uri'] .= self::ENDPOINT_RECORD;
            $query['method'] = 'GET';
        } elseif (array_key_exists('-find', $params)) {
            $query['uri'] .= self::ENDPOINT_FIND;
            $query['method'] = 'POST';
            $query['body'] = array_merge(
                self::parseRange($params),
                self::parseLayoutResponse($params),
                self::parseScripts($params),
                self::parseFind($params),
                self::parseSort($params)
            );
        } elseif (array_key_exists('-findquery', $params)) {
            $query['uri'] .= self::ENDPOINT_FIND;
            $query['method'] = 'POST';
            $query['body'] = array_merge(
                self::parseRange($params),
                self::parseLayoutResponse($params),
                self::parseScripts($params),
                self::parseFindQuery($params),
                self::parseSort($params)
            );
        } elseif (array_key_exists('-findall', $params)) {
            $query['uri'] .= self::ENDPOINT_RECORDS;
            $query['method'] = 'GET';
            $query['queryParams'] = array_merge(
                $query['queryParams'],
                self::parseRange($params, '_'),
                self::parseLayoutResponse($params),
                self::parseScripts($params, true),
                self::parseSort($params, '_', true)
            );
        } elseif (array_key_exists('-new', $params)) {
            $query['uri'] .= self::ENDPOINT_RECORDS;
            $query['method'] = 'POST';
            if ($fieldData = self::parseFields($params)) {
                $query['body']['fieldData'] = $fieldData;
            }
            if ($portalData = self::parsePortalFields($params)) {
                $query['body']['portalData'] = $portalData;
            }
            $query['body'] = array_merge(
                $query['body'],
                self::parseScripts($params)
            );
        } elseif (array_key_exists('-edit', $params)) {
            $query['uri'] .= self::ENDPOINT_RECORD;
            $query['method'] = 'PATCH';
            $query['body'] = [];
            if ($fieldData = self::parseFields($params)) {
                $query['body']['fieldData'] = $fieldData;
            }
            if ($portalData = self::parsePortalFields($params)) {
                $query['body']['portalData'] = $portalData;
            }
            $query['body'] = array_merge(
                $query['body'],
                self::parseScripts($params)
            );
            if (isset($params['-delete.related'])) {
                $query['body']['fieldData']['deleteRelated'] = [$params['-delete.related']];
            }
        } elseif (array_key_exists('-delete', $params)) {
            $query['uri'] .= self::ENDPOINT_RECORD;
            $query['method'] = 'DELETE';
            $query['queryParams'] = array_merge(
                $query['queryParams'],
                self::parseScripts($params)
            );
        } elseif (array_key_exists('-dup', $params)) {
            $query['uri'] .= self::ENDPOINT_RECORD;
            $query['method'] = 'POST';
            $query['body'] = array_merge(
                self::parseScripts($params)
            );
        } elseif (array_key_exists('-dbnames', $params)) {
            $query['uri'] .= self::ENDPOINT_DATABASES;
            $query['method'] = 'GET';
        } elseif (array_key_exists('-scriptnames', $params)) {
            $query['uri'] .= self::ENDPOINT_SCRIPTS;
            $query['method'] = 'GET';
        } elseif (array_key_exists('-layoutnames', $params)) {
            $query['uri'] .= self::ENDPOINT_LAYOUTS;
            $query['method'] = 'GET';
        } elseif (array_key_exists('-view', $params)) {
            $query['uri'] .= self::ENDPOINT_METADATA;
            $query['method'] = 'GET';
        } elseif (array_key_exists('-performscript', $params)) { //Custom handler
            $query['uri'] .= self::ENDPOINT_SCRIPT;
            $query['method'] = 'GET';
            $query['params']['scriptName'] = rawurlencode($params['-script']);
            $query['queryParams']['script.param'] =  rawurlencode(@$params['-script.param']);
        } elseif (array_key_exists('-setGlobals', $params)) { //Custom handler
            $query['uri'] .= self::ENDPOINT_GLOBAL;
            $query['method'] = 'PATCH';
            $query['body'] = [
                'globalFields' => self::parseFields($params)
            ];
        } elseif (array_key_exists('-findany', $params)) {
            throw new Exception('Find any function not supported by dataAPI');
        }

        //$query['uri'] = str_replace(array_keys($query['params']), array_values($query['params']), $query['uri']);

        return $query;
    }

    /**
     * @param array $params
     * @param string $prefix
     * @param bool $encoded
     * @return array
     */
    public static function parseSort(array $params, $prefix = null, $encoded = false)
    {
        $sort = [];

        foreach ($params as $key => $field) {
            if (substr($key, 0, 11) === '-sortfield.') {
                $precedence = (int) substr($key, 11, strlen($key)-11);
                $sort[$precedence] = [
                    'fieldName' => $encoded ? rawurlencode($field) : $field,
                    'sortOrder' => array_key_exists('-sortorder.' . $precedence, $params) ?
                        $params['-sortorder.' . $precedence]
                        : 'ascend',
                ];
            }
        }
        if ($sort) {
            return [$prefix.'sort' => array_values($sort)];
        }
        return [];
    }

    /**
     * @param array $params
     * @param string $prefix
     * @return array
     */
    public static function parseRange(array $params, $prefix = null)
    {
        $range = [];

        if (array_key_exists('-skip', $params)) {
            $range[$prefix . 'offset'] = $params['-skip'];
        }
        if (array_key_exists('-max', $params)) {
            $range[$prefix .'limit'] = $params['-max'];
        }

        return $range;
    }

    public static function parseLayoutResponse(array $params)
    {
        if (array_key_exists('-lay.response', $params)) {
            return ['layout.response' => $params['-lay.response']];
        }
        return [];
    }

    public static function parseScripts(array $params, $encoded = false)
    {
        $scripts = [];
        if (array_key_exists('-script', $params)) {
            $scripts['script'] = $params['-script'];
            $scripts['script.param'] = @$params['-script.param'];
        }
        if (array_key_exists('-script.prefind', $params)) {
            $scripts['script.prerequest'] = $params['-script.prefind'];
            $scripts['script.prerequest.param'] = @$params['-script.prefind.param'];
        }
        if (array_key_exists('-script.presort', $params)) {
            $scripts['script.presort'] = $params['-script.presort'];
            $scripts['script.presort.param'] = @$params['-script.presort.param'];
        }
        if ($encoded) {
            foreach($scripts as $key => $value) {
                $scripts[$key] = rawurldecode($value);
            }
        }
        return $scripts;
    }

    /**
     * @param array $params
     * @return \array[][]
     */
    public static function parseFind(array $params)
    {
        $crits = self::parseFields($params);
        return ['query' => [$crits]];
    }

    /**
     * @param array $params
     * @return array[]
     */
    public static function parseFindQuery(array $params)
    {
        $queries = [];
        $requests = explode(';', $params['-query']);
        $searchCriterias = [];
        foreach ($params as $key => $field) {
            if (preg_match('/-q\d+/', $key) && !strpos($key, '.')) {
                $index = (int) substr($key, 2, 999);
                $searchCriterias[$index] = [ $field => $params['-q' . $index . '.value']];
            }
        }

        foreach ($requests as $request) {
            $query = [];

            if(substr($request, 0, 1) === '!') {
                $query['omit'] = "true";
            }
            preg_match('/\(([^\)]*)/', $request, $matches);
            $criterias = explode(',', $matches[1]);
            foreach ($criterias as $index) {
                $i = preg_replace('/[^0-9]/', '', $index);
                $query[rawurlencode(key($searchCriterias[$i]))] = $searchCriterias[$i][key($searchCriterias[$i])];
            }
            $queries[] = $query;
        }
        return ['query' => $queries];
    }


    public static function parseFields(array $params)
    {
        $fieldData = [];
        $fields = [
            array_filter($params, function($key) {
                return substr($key, 0, 1) !== '-'
                    && !preg_match('/\.\d+$/', $key); //Ignore portal fields here
            }, ARRAY_FILTER_USE_KEY)
        ];

        foreach ($fields[0] as $field => $value) {
            preg_match('/(?<field>.*)(?<global>\.global)?$/U', $field, $matches);
            if (!empty($matches['global'])) {
                #TODO Handle globals
            } else {
                $fieldData[urldecode($field)] = urldecode($value);
            }
        }
        return $fieldData;
    }

    public static function parseGlobalFields(array $params)
    {
        $fieldData = [];
        $fields = [
            array_filter($params, function($key) {
                return substr($key, 0, 1) !== '-'
                    && !preg_match('/\.\d+$/', $key); //Ignore portal fields here
            }, ARRAY_FILTER_USE_KEY)
        ];

        foreach ($fields[0] as $field => $value) {
            preg_match('/(?<field>.*)(?<global>\.global)?$/U', $field, $matches);
            if (!empty($matches['global'])) {
                $fieldData[$matches['field']] = $value;
            }
        }
        return $fieldData;
    }

    public static function appendTableToGlobals($globals, $table)
    {
        $globalResult = [];
        foreach ($globals as $field => $val) {
            if (!strpos($field, '::')) {
                $globalResult[$table . '::' . $field] = $val;
            } else {
                $globalResult[$field] = $val;
            }
        }
        return $globalResult;
    }

    /**
     * @param array $params
     * @return array
     */
    public static function parsePortalFields(array $params)
    {
        $portalData = [];
        if (!isset($params['-relatedSet'])) {
            return $portalData;
        }
        $portal = $params['-relatedSet'];
        $fields = [
            array_filter($params, function($key) {
                return substr($key, 0, 1) !== '-'
                    && preg_match('/\.\d+$/', $key); //Ignore portal fields here
            }, ARRAY_FILTER_USE_KEY)
        ];

        if (!isset($portalData[$portal])) {
            $portalData[$portal] = [];
        }

        foreach ($fields[0] as $field => $value) {
            preg_match('/(?<table>.+)::(?<field>.+)\.(?<recordId>\d+)$/U', $field, $matches);

            if (!isset($portalData[$portal][$matches['recordId']])) {
                $portalData[$portal][$matches['recordId']] = [
                ];
                if ($matches['recordId'] != 0) {
                    $portalData[$portal][$matches['recordId']]["recordId"] = $matches['recordId'];
                }
            }
            $portalData[$portal][$matches['recordId']][$matches['table'] . '::' . $matches['field']] = $value;
        }

        //Reset recid index to pass json objects
        foreach ($portalData as $portal => $records) {
            $portalData[$portal] = array_values($records);
        }
        return $portalData;
    }

}