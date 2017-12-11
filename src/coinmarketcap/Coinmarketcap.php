<?php
/**
 * Created by PhpStorm.
 * User: vorapoap
 * Date: 11/12/2560
 * Time: 10:34
 */

namespace vorapoap\coinmarketcap;

use \PDO;


class Coinmarketcap
{
    protected $pdo;
    protected $tableName;
    protected $defaultLimit;
    protected $defaultUpdateInterval;

    public function __construct(array $config) {
        if ($config['pdo'])
            $this->pdo = $pdo;
        elseif ($config['dsn']) {
            $dsn = $config['dsn'];
            $this->pdo = new PDO($dsn);
        } else {
            $options = [];
            $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'];
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], $options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }
        $this->defaultLimit = $config['coinmarketcap-limit'];
        if (!$this->defaultLimit) $this->defaultLimit = 200;
        $this->defaultUpdateInterval = $config['coinmarketcap-update-interval'];
        if (!$this->defaultUpdateInterval) $this->defaultUpdateInterval = 60;

        $this->tableName = $config['prefix'] . 'coinmarketcap';
        $this->install();
    }

    private function install() {
        $sql = "CREATE TABLE `coinmarketcap` (
                `id` VARCHAR(50) NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `symbol` VARCHAR(8) NOT NULL,
                `rank` INT(11) NOT NULL,
                `price_usd` DECIMAL(10,2) NOT NULL,
                `price_btc` DECIMAL(10,2) NOT NULL,
                `24h_volume_usd` DECIMAL(10,2) NOT NULL,
                `market_cap_usd` DECIMAL(10,2) NOT NULL,
                `available_supply` BIGINT(20) NOT NULL,
                `total_supply` BIGINT(20) NOT NULL,
                `max_supply` BIGINT(20) NULL DEFAULT NULL,
                `percent_change_1h` DECIMAL(4,2) NOT NULL,
                `percent_change_24h` DECIMAL(4,2) NOT NULL,
                `percent_change_7d` DECIMAL(4,2) NOT NULL,
                `last_updated` BIGINT(20) NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `name` (`name`),
                INDEX `symbol` (`symbol`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB;";
        try {
            $this->pdo->query("SELECT 1 FROM {$this->tableName} LIMIT 1");
        } catch (Exception $e) {
            // We got an exception == table not found
            $createTable = $this->pdo->exec($sql);
            if (!$createTable) {
                throw new \Exception("Unable to crete coinmarketcap table");
            }
        }
    }
    public function checkToUpdate($interval = NULL) {
        if (!$interval) $interval = $this->defaultUpdateInterval;
        $stmt = $this->pdo->prepare("SELECT last_updated FROM ".$this->tableName. " WHERE symbol = 'BTC' LIMIT 1");
        $stmt->execute();
        $last = $stmt->fetchColumn(0);
        if (!$last || $last < time() - $interval) {
            $this->update();
            return TRUE;
        }
        return FALSE;
    }

    public function update($limit = NULL) {
        if (!$limit) $limit = $this->defaultLimit;
        $data = file_get_contents("https://api.coinmarketcap.com/v1/ticker/?limit=" . $limit);
        $data = json_decode($data, TRUE);
        $keys = array_keys($data[0]);
        $keysvar = array_map(function ($a) {
            return ':' . $a;
        }, $keys);
        $this->pdo->exec("TRUNCATE TABLE " . $this->tableName);
        $prepare_str = "INSERT INTO " . $this->tableName . " (" . implode(",", $keys) . ") VALUES (" . implode(",", $keysvar) . ")";
        $stmt = $this->pdo->prepare($prepare_str);
        foreach ($data as $rowno => $row) {
            foreach (array_keys($row) as $k) {
                $stmt->bindParam(':' . $k, $data[$rowno][$k], PDO::PARAM_STR);
            }
            $stmt->execute();
        }
    }

    public function getAllCoinData() {
        $this->checkToUpdate();
        $stmt = $this->pdo->prepare("SELECT * FROM " . $this->tableName);
        $stmt->execute();
        $rows = [];
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //select column by key and use
            $rows[] = $result;
        }
        return $rows;
    }

    protected function getCoin($ticker) {
        $this->checkToUpdate();
        $stmt = $this->pdo->prepare("SELECT * FROM " . $this->tableName." WHERE `id` = :id OR `name` = :id OR LOWER(`symbol`) = :id");
        $stmt->bindParam(':id', $ticker);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}