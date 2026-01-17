<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDO;
use PDOException;

class TestDbController extends Controller
{
    protected $mydb;

    public function testDB(Request $request)
    {

        $request->session()->put('env.DB_CONNECTION', $request->db_connection);
        $request->session()->put('env.DB_HOST', $request->db_host);
        $request->session()->put('env.DB_PORT', $request->db_port);
        $request->session()->put('env.DB_DATABASE', $request->db_database);
        $request->session()->put('env.DB_USERNAME', $request->db_username);
        $request->session()->put('env.DB_PASSWORD', $request->db_password);

        if ($request->db_connection == 'mysql') {
            return $this->testMySql();
        }

        if ($request->db_connection == 'pgsql') {
            return $this->testPostgreSQL();
        }

        return response()->json([
            'Error' => 'DB Type not Supported for testing',
            'State' => '999',
        ]);

    }

    public function testMySql()
    {
        $db_type = session('env.DB_CONNECTION');
        $db_host = session('env.DB_HOST');
        $db_name = session('env.DB_DATABASE');
        $db_user = session('env.DB_USERNAME');
        $db_pass = session('env.DB_PASSWORD');
        $db_port = session('env.DB_PORT');

        if (! $db_name) {
            return response()->json([
                'Error' => 'No Database',
                'State' => '999',
            ]);
        }

        if (! $db_user) {
            return response()->json([
                'Error' => 'No Username',
                'State' => '999',
            ]);
        }

        if (! $db_port) {
            return response()->json([
                'Error' => 'No Port',
                'State' => '999',
            ]);
        }

        if (! $db_host) {
            return response()->json([
                'Error' => 'No Host',
                'State' => '999',
            ]);
        }

        try {
            $db = new PDO($db_type.':host='.$db_host.';port='.$db_port.';dbname='.$db_name, $db_user, $db_pass, [PDO::ATTR_TIMEOUT => '5', PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
        } catch (PDOException $e) {

            if ($e->getCode() == '1049' && ! $db_name == '') {
                $db = new PDO($db_type.':host='.$db_host.';port='.$db_port.';dbname='.'', $db_user, $db_pass, [PDO::ATTR_TIMEOUT => '5', PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
                $db->query("CREATE DATABASE IF NOT EXISTS $db_name");

                return response()->json([
                    'State' => '200',
                    'Success' => 'Database '.$db_name.' created',
                ]);

            }

            return response()->json([
                'Error' => $e->getMessage(),
                'State' => $e->getCode(),
            ]);

        }

        return response()->json([
            'State' => '200',
            'Success' => 'Seems okay',
        ]);

    }

    public function testPostgreSQL()
    {
        $db_type = session('env.DB_CONNECTION');
        $db_host = session('env.DB_HOST');
        $db_name = session('env.DB_DATABASE');
        $db_user = session('env.DB_USERNAME');
        $db_pass = session('env.DB_PASSWORD');
        $db_port = session('env.DB_PORT') ?: '5432';

        if (! $db_name) {
            return response()->json([
                'Error' => 'No Database',
                'State' => '999',
            ]);
        }

        if (! $db_user) {
            return response()->json([
                'Error' => 'No Username',
                'State' => '999',
            ]);
        }

        if (! $db_port) {
            return response()->json([
                'Error' => 'No Port',
                'State' => '999',
            ]);
        }

        if (! $db_host) {
            return response()->json([
                'Error' => 'No Host',
                'State' => '999',
            ]);
        }

        try {
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";
            $db = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            // Try to connect without database to create it if it doesn't exist
            if ($e->getCode() == '7' || strpos($e->getMessage(), 'does not exist') !== false) {
                try {
                    $dsn_no_db = "pgsql:host={$db_host};port={$db_port};dbname=postgres";
                    $db = new PDO($dsn_no_db, $db_user, $db_pass, [
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    $db->exec("CREATE DATABASE \"{$db_name}\"");

                    return response()->json([
                        'State' => '200',
                        'Success' => 'Database '.$db_name.' created',
                    ]);
                } catch (PDOException $e2) {
                    return response()->json([
                        'Error' => $e2->getMessage(),
                        'State' => $e2->getCode(),
                    ]);
                }
            }

            return response()->json([
                'Error' => $e->getMessage(),
                'State' => $e->getCode(),
            ]);
        }

        return response()->json([
            'State' => '200',
            'Success' => 'Seems okay',
        ]);
    }
}
