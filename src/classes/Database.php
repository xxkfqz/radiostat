<?php

class Database
{
    private string $pathToDatabaseFile;
    private SQLite3 $connection;

    /**
     * @throws Exception
     */
    public function __construct(string $pathToDatabaseFile)
    {
        if (empty($this->pathToDatabaseFile = $pathToDatabaseFile)) {
            $this->pathToDatabaseFile = ':memory:';
        }

        $this->checkExtensions();
        $this->openSqliteConnection();
        $this->checkTables();
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * @throws Exception
     */
    private function checkExtensions()
    {
        if (!extension_loaded('sqlite3')) {
            throw new Exception('SQLite3 extension is not loaded!');
        }

        if (!extension_loaded('hash')) {
            throw new Exception('Hash extension is not loaded!');
        }
    }

    private function openSqliteConnection()
    {
        $this->connection = new SQLite3($this->pathToDatabaseFile);
    }

    private function checkTables()
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS listeners(name TEXT, code TEXT, time DATETIME, listeners INT)'
        );
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS history(code TEXT, artist TEXT, title TEXT, time DATETIME, checksum TEXT UNIQUE)'
        );
    }

    public function writeListeners(string $name, string $code, int $listeners)
    {
        $name = \SQLite3::escapeString($name);
        $code = \SQLite3::escapeString($code);
        $listeners = \SQLite3::escapeString($listeners);
        $time = $this->formatTime(time());
        $this->connection->exec(
            "INSERT INTO listeners(name, code, time, listeners) VALUES('$name', '$code', '$time', '$listeners')"
        );
    }

    public function writeHistory(string $code, string $artist, string $trackTitle, string $time)
    {
        $artist = \SQLite3::escapeString($artist);
        $code = \SQLite3::escapeString($code);
        $trackTitle = \SQLite3::escapeString($trackTitle);
        $time = $this->formatTime($time);

        $checksum = hash('sha256', "{$artist}___{$trackTitle}___{$time}", false);
        $this->connection->exec(
            "INSERT OR IGNORE INTO history(code, artist, title, time, checksum) VALUES('$code', '$artist', '$trackTitle', '$time', '$checksum')"
        );
    }

    private function formatTime($timestamp): bool|string
    {
        return strftime('%Y-%m-%d %H:%M:%S', $timestamp);
    }

    public function getHistory(): array
    {
        $raw = $this->connection->query(
            "SELECT code, artist, title FROM history WHERE time > datetime('now' , '-7 days') ORDER BY time DESC LIMIT 100"
        );

        $rawResult = [];
        while ($row = $raw->fetchArray(SQLITE3_ASSOC)) {
            $rawResult[$row['code']][] = $row;
        }

        return $rawResult;
    }

    public function getListeners(): array
    {
        $raw = $this->connection->query(
            "SELECT * FROM listeners WHERE time > datetime('now' , '-7 days') ORDER BY time"
        );

        $result = [];
        while ($row = $raw->fetchArray(SQLITE3_ASSOC)) {
            $date = (DateTime::createFromFormat('Y-m-d H:i:s', explode('.', $row['time'])[0]))->format('Y.m.d H:i');

            if (empty($result[$row['code']][$date])) {
                $result[$row['code']][$date] = 0;
            }

            $result[$row['code']][$date] += $row['listeners'];
        }

        return $result;
    }
}