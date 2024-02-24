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
    private function checkExtensions(): void
    {
        if (!extension_loaded('sqlite3')) {
            throw new Exception('SQLite3 extension is not loaded!');
        }

        if (!extension_loaded('hash')) {
            throw new Exception('Hash extension is not loaded!');
        }
    }

    private function openSqliteConnection(): void
    {
        $this->connection = new SQLite3($this->pathToDatabaseFile);
    }

    private function checkTables(): void
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS listeners(name TEXT, code TEXT, time DATETIME, listeners INT)'
        );
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS history(code TEXT, track INT, time DATETIME)'
        );
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS tracks(artist TEXT, title TEXT)'
        );
    }

    public function writeListeners(string $name, string $code, int $listeners): void
    {
        $name = \SQLite3::escapeString($name);
        $code = \SQLite3::escapeString($code);
        $listeners = \SQLite3::escapeString($listeners);
        $time = $this->formatTime(time());
        $this->connection->exec(
            "INSERT INTO listeners(name, code, time, listeners) VALUES('$name', '$code', '$time', '$listeners')"
        );
    }

    public function getTrackId($artist, $title)
    {
        $artist = \SQLite3::escapeString($artist);
        $title = \SQLite3::escapeString($title);

        $raw = $this->connection->query(
            "SELECT rowid, artist, title FROM tracks WHERE artist = '$artist' AND title = '$title'"
        );
        $result = $raw->fetchArray(SQLITE3_ASSOC);
        if (!empty($result)) {
            return $result['rowid'];
        }

        $this->connection->exec(
            "INSERT OR IGNORE INTO tracks(artist, title) VALUES('$artist', '$title')"
        );

        return $this->connection->lastInsertRowID();
    }

    public function writeHistory(string $code, string $artist, string $trackTitle, string $time): void
    {
        $code = \SQLite3::escapeString($code);
        $time = $this->formatTime($time);
        $trackId = $this->getTrackId($artist, $trackTitle);

        // TODO: maybe it should be single query
        $result = $this->connection->query(
            "SELECT code, track, time FROM history WHERE code = '$code' AND track = '$trackId' AND time = '$time'"
        )->fetchArray(SQLITE3_ASSOC);
        if (!empty($result)) {
            return;
        }

        $this->connection->exec(
            "INSERT OR IGNORE INTO history(code, track, time) VALUES('$code', '$trackId', '$time')"
        );
    }

    private function formatTime($timestamp): bool|string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function getHistory(): array
    {
        $raw = $this->connection->query(
            "SELECT code, track FROM history WHERE time > datetime('now' , '-7 days') ORDER BY time DESC LIMIT 100"
        );

        $rawResult = [];
        while ($row = $raw->fetchArray(SQLITE3_ASSOC)) {
            $trackRow = $this->connection->query(
                "SELECT artist, title FROM tracks WHERE rowid = '{$row['track']}'"
            )->fetchArray(SQLITE3_ASSOC);

            $trackRow = array_map(function ($str) {
                return stripslashes($str);
            }, $trackRow);
            $rawResult[$row['code']][] = $trackRow;
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