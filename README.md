# coinmarketcap
Store and retreive Coinmarketcap's data from local database

### Install using composer

Create composer.json file
```
{
    "name": "yourproject/yourproject",
    "type": "project",
    "require": {
        "vorapoap/coinmarketcap": "*"
    }
}
```
and run ```composer update```  or run this command in your command line:
```composer require voapoap/coinmarketcap```

### 1. Define options
 1. Use existing PDO connection
    ```sh
    $options['pdo'] = $existingPDOConnection;
    ```
 2. Use new connection
    ```sh
    $options['host'] = 'localhost';
    $options['database'] = 'coinmarketcap';
    $options['user'] = 'user';
    $options['password'] = 'password';
    ```
### 2. Create object
```sh
$coin = new Coinmarketcap($options);
```
On object constructing, the table will be checked for existence. If the table doesn't exist, the required table is create, you can also define $options['prefix'] to prefix the table.

Other options
| prefix | Table prefix |
| --- | --- |
| coinmarketcap-limit |  Default is 100 (See [https://coinmarketcap.com/api/]) |
| coinmarketcap-update-interval | Update interval, default is 60s | 

### 3. Usage
You can also do $coin->update(150) to override default coin limit
```sh
$coin = new Coinmarketcap($options);
$coin->update(); 
$coin->getCoin("btc");
$coin->getCoin("bitcoin");
```

Automtically retrieve data from Coinmarketcap base on update interval.
```sh
$coin = new Coinmarketcap($options);
$coin->getCoin("btc"); 
```

### 4. Donation

If you like this work and like to donate:

BTC:	13SsoAUNv1KobhnDSZytLo71NHAGu9XxUd
ETH:	0x1E0d07890Cb550F18eE6B80dC5739CFe776C72b5
