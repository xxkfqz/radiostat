<?php

class StatParser
{
    const URL_LISTENERS = 'https://station.waveradio.org/status-json.xsl';
    const URL_HISTORY = 'https://core.waveradio.org/public/history';
    const CODES = ['provodach', 'witch', 'soviet'];
    const HISTORY_AMOUNT = 5;
    private CurlHandle $curl;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->checkExtensions();

        if (!($this->curl = curl_init())) {
            throw new Exception('Can not create curl context!');
        }

        curl_setopt_array(
            $this->curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function checkExtensions()
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Curl extension is not loaded!');
        }

        if (!extension_loaded('json')) {
            throw new Exception('Json extension is not loaded!');
        }
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function parseListeners(): array
    {
        curl_setopt($this->curl, CURLOPT_URL, self::URL_LISTENERS);
        $response = curl_exec($this->curl);
        if (empty($response)) {
            throw new Exception('Can not parse listeners');
        }

        $result = [];
        foreach (json_decode($response, true)['icestats']['source'] as $station) {
            $stationName = explode('/', $station['listenurl'])[3];
            $stationCode = match ($station['server_url']) {
                'https://provoda.ch' => 'provodach',
                'https://sovietwave.su' => 'soviet',
                'https://witch.waveradio.org' => 'witch'
            };

            $result[] = [
                'name' => $stationName,
                'code' => $stationCode,
                'listeners' => $station['listeners']
            ];
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function parseHistory(): array
    {
        $result = [];
        foreach (self::CODES as $code) {
            $httpParams = http_build_query(
                [
                    'station' => $code,
                    'amount' => self::HISTORY_AMOUNT
                ]
            );
            curl_setopt($this->curl, CURLOPT_URL, self::URL_HISTORY . "?$httpParams");
            $response = curl_exec($this->curl);
            if (empty($response)) {
                throw new Exception("Can not parse history for code '$code'");
            }

            foreach (json_decode($response, true)['payload'] as $entry) {
                $result[] = [
                    'code' => $code,
                    'artist' => $entry['artist'],
                    'title' => $entry['track_title'],
                    'time' => $entry['start_time']
                ];
            }
        }

        return $result;
    }
}