<?php

class Html
{
    private DateTime $nowTimeDT;
    private Database $db;

    /**
     * @throws Exception
     */
    public function __construct($pathToDb)
    {
        require_once 'Database.php';

        $this->db = new Database($pathToDb);

        $this->nowTimeDT = new DateTime;
        $this->nowTimeDT->setTimezone(new DateTimeZone('UTC'));
    }

    public function getHistory(): array
    {
        return $this->db->getHistory();
    }

    public function getListeners(): array
    {
        return $this->db->getListeners();
    }

    public function getStringTime(): string
    {
        return $this->nowTimeDT->format('Y.m.d H:i');
    }

    public function getIsoTime(): string
    {
        return $this->nowTimeDT->format('c');
    }

    public function getLabels(): string
    {
        return implode(',', array_map(function ($date) {
            return "'$date'";
        }, array_keys($this->getListeners()['provodach'])));
    }

    public function formatChartValues(): string
    {
        $str = [];
        foreach ($this->getListeners() as $code => $values) {
            $vals = implode(',', array_values($values));
            $str[] = "const data_$code = [$vals];";
        }

        return implode('', $str);
    }
}