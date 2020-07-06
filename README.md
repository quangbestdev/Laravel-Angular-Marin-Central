# MSP-Backend

Marine Service Pros Backend

This platform is built using laravel5.6

## Server Requirements

* PHP >= 7.1.3
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension
* Tokenizer PHP Extension
* XML PHP Extension
* Ctype PHP Extension
* JSON PHP Extension

## Setup Steps

1. Install Apache Server

    ```
    $ sudo apt-get update
    $ sudo apt-get install apache2
    ```

Link:`https://www.digitalocean.com/community/tutorials/how-to-install-the-apache-web-server-on-ubuntu-16-04`

2. Install PHP7.2

3. Install PHP7.2 extensions  
    ```
    $ sudo apt-get install php-pear php7.2-curl php7.2-dev php7.2-mbstring php7.2-zip php7.2-mysql php7.2-xml php7.2-pgsql
    ```

4. Install git  
    ```
    $ sudo apt install git
    ```

5. Install Postgres  
    ```
    $ sudo apt-get install postgresql postgresql-contrib
    ```

* Set Password for Postgres

  ```
  $ sudo -u postgres psql
  ```

* Then:

  ```
  \password postgres
  ```
  * Then to quit psql:

  ```
  \q
  ```

* If that does not work, reconfigure authentication.

* Edit /etc/postgresql/9.1/main/pg_hba.conf (path will differ) and change:

    
```
local      all      all     peer
```

to:

    local   all    all   md5

* Then restart the server:

  ```
  $ sudo service postgresql restart
  ```

* Create Database:  
    ```
    $ createdb -h localhost -p 5432 -U postgres marine-central
    ```

Link: `https://www.digitalocean.com/community/tutorials/how-to-install-and-use-postgresql-on-ubuntu-16-04`

6. Install composer  
    ```
    $ sudo apt install composer
    ```

7. Clone the respo `MC-web-back-end` and add .env file at the root of the project.

8. Navigate to root and run
    ```
    $ composer install
    $ php artisan migrate
    $ php artisan db:seed
    ```
  