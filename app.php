#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

use PAGI\Application\PAGIApplication;
use PAGI\Client\Impl\ClientImpl as PagiClient;

class TeleAirport extends PAGIApplication
{
    protected $agi;
    protected $asteriskLogger;
    protected $channelVairables;
    protected $connection;

    public function log($msg)
    {
        $agi = $this->getAgi();
        $this->logger->debug($msg);
        $agi->consoleLog($msg);
    }

    private function getCity($city)
    {
        switch ($city) {
            case 'tuzla':
                return 'airpot/tuzla';
                break;
            case 'sarajevo':
                return 'airport/sarajevo';
                break;
            case 'newyork':
                return 'airport/new_york';
                break;
            case 'london':
                return 'airport/london';
                break;
            default:
                return 'airport/wrong_option';
                break;
        }
    }


    private function getStatus($status)
    {
        if ($status == 'delayed') {
            return 'airport/delayed';
        } else {
            return 'airport/on_time';
        }
    }

    private function flightNumber($option, $client)
    {
        $result = $this->connection->query("SELECT * FROM flight WHERE flight_number=$option");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $client->streamFile('airport/flight_number', '#');
            $client->sayDigits($row['flight_number'], '#');
            $client->streamFile('airport/from', '#');
            $client->streamFile($this->getCity($row['origin']), '#');
            $client->streamFile('airport/to', '#');
            $client->streamFile($this->getCity($row['destination']), '#');
            $client->streamFile($this->getStatus($row['status']), '#');
            $this->run();
        } else {
            $client->streamFile('airport/no_flight', '#');
            $this->run();
        }
    }

    private function flightByOrigin($option, $client)
    {
        $city = "";
        switch ($option) {
            case 1:
                $city = "sarajevo";
                break;
            case 2:
                $city = "london";
                break;
            case 3:
                $city = "newyork";
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }

        $result = $this->connection->query("SELECT * FROM flight WHERE origin=$city");
        $this->log($result);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $client->streamFile('airport/flight_number', '#');
                $client->sayDigits($row['flight_number'], '#');
                $client->streamFile('airport/from', '#');
                $client->streamFile($this->getCity($row['origin']), '#');
                $client->streamFile('airport/to', '#');
                $client->streamFile($this->getCity($row['destination']), '#');
                $client->streamFile($this->getStatus($row['status']), '#');
            }
            $this->run();
        } else {
            $client->streamFile('airport/no_current_flights', '#');
            $client->streamFile('airport/from', '#');
            $client->streamFile($this->getCity($city), '#');
            $this->run();
        }
    }

    private function flightByDestination($option, $client)
    {
        $city = "";
        switch ($option) {
            case 1:
                $city = "sarajevo";
                break;
            case 2:
                $city = "london";
                break;
            case 3:
                $city = "newyork";
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }

        $result = $this->connection->query("SELECT * FROM flight WHERE destination=$city");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $client->streamFile('airport/flight_number', '#');
                $client->sayDigits($row['flight_number'], '#');
                $client->streamFile('airport/from', '#');
                $client->streamFile($this->getCity($row['origin']), '#');
                $client->streamFile('airport/to', '#');
                $client->streamFile($this->getCity($row['destination']), '#');
                $client->streamFile($this->getStatus($row['status']), '#');
            }
            $this->run();
        } else {
            $client->streamFile('airport/no_current_flights', '#');
            $client->streamFile('airport/to', '#');
            $client->streamFile($this->getCity($city), '#');
            $this->run();
        }
    }


    private function arrivials($option, $client)
    {
        $result = $client->getData('airport/menu_1_1', 10000, 1);
        if (!$result->isTimeout()) {
            $option = $result->getDigits();
        } else {
            $this->log('Timeouted for get data with: ' . $result->getDigits());
        }

        switch ($option) {
            case 1:
                $result = $client->getData('airport/menu_1_1_1', 10000, 5);
                if (!$result->isTimeout() && $result->getDigitsCount() == 5) {
                    $option = $result->getDigits();
                    $this->flightNumber($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            case 2:
                $result = $client->getData('airport/menu_1_1_2', 10000, 1);
                if (!$result->isTimeout()) {
                    $option = $result->getDigits();
                    $this->flightByOrigin($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }
    }

    private function departures($option, $client)
    {

        $result = $client->getData('airport/menu_1_2', 10000, 1);
        if (!$result->isTimeout()) {
            $option = $result->getDigits();
        } else {
            $this->log('Timeouted for get data with: ' . $result->getDigits());
        }

        switch ($option) {
            case 1:
                $result = $client->getData('airport/menu_1_2_1', 10000, 5);
                if (!$result->isTimeout() && $result->getDigitsCount() == 5) {
                    $option = $result->getDigits();
                    $this->flightNumber($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            case 2:
                $result = $client->getData('airport/menu_1_2_2', 10000, 1);
                if (!$result->isTimeout()) {
                    $option = $result->getDigits();
                    $this->flightByDestination($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }
    }

    private function switchFlights($option, $client)
    {
        switch ($option) {
            case 1:
                $this->arrivials($option, $client);
                break;
            case 2:
                $this->departures($option, $client);
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }
    }

    private function flightStatus($option, $client)
    {
        switch ($option) {
            case 1:
                $result = $client->getData('airport/menu_1', 10000, 1);
                if (!$result->isTimeout()) {
                    $option = $result->getDigits();
                    $this->switchFlights($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }
    }

    private function checkAvailableFlights($option, $client)
    {
        $result = $client->getData('airport/menu_1_2_2', 10000, 1);
        if (!$result->isTimeout()) {
            $option = $result->getDigits();
        } else {
            $this->log('Timeouted for get data with: ' . $result->getDigits());
        }

        $city = "";
        switch ($option) {
            case 1:
                $city = "sarajevo";
                break;
            case 2:
                $city = "london";
                break;
            case 3:
                $city = "newyork";
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }

        $result = $this->connection->query("SELECT * FROM flight WHERE destination=$city AND status=\"available\"");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $client->streamFile('airport/flight_number', '#');
                $client->sayDigits($row['flight_number'], '#');
                $client->streamFile('airport/from', '#');
                $client->streamFile($this->getCity($row['origin']), '#');
                $client->streamFile('airport/to', '#');
                $client->streamFile($this->getCity($row['destination']), '#');
                $client->streamFile($this->getStatus($row['status']), '#');
            }
            $this->run();
        } else {
            $client->streamFile('airport/no_available_flights', '#');
            $client->streamFile('airport/to', '#');
            $client->streamFile($this->getCity($city), '#');
            $this->run();
        }
    }

    private function flightAvailable($client, $option)
    {
        switch ($option) {
            case 1:
                $result = $client->getData('airport/menu_2', 10000, 1);
                if (!$result->isTimeout()) {
                    $option = $result->getDigits();
                    $this->checkAvailableFlights($option, $client);
                } else {
                    $this->log('Timeouted for get data with: ' . $result->getDigits());
                }
                break;
            default:
                $client->streamFile('airport/wrong_option', '#');
                $this->run();
                break;
        }
    }

    public function welcomeMessage()
    {
        $this->getAgi()->streamFile(
            'airport/welcome.3',
            '#'
        );
    }

    public function run()
    {
        $this->asteriskLogger->notice("Run");
        $this->logger->info("Run");
        $client = $this->getAgi();

        $result = $client->getData('airport/menu', 10000, 1);
        if (!$result->isTimeout()) {
            $option = $result->getDigits();
            switch ($option) {
                case 1:
                    $this->flightStatus($option, $client);
                    break;
                case 2:
                    $this->flightAvailable($option, $client);
                    break;
                default:
                    $client->streamFile('airport/wrong_option', '#');
                    $this->run();
                    break;
            }
        } else {
            $this->log('Timeouted for get data with: ' . $result->getDigits());
        }

        $this->connection->close();
        $client->streamFile('airport/goodbye', "#");
        $client->hangup();
    }

    public function init()
    {
        $this->logger->info('Init');

        $this->connection = new mysqli('localhost', 'root', 'root', 'airport');
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        $this->logger->info("Connected to database");

        $this->agi = $this->getAgi();
        $this->asteriskLogger = $this->agi->getAsteriskLogger();
        $this->channelVariables = $this->agi->getChannelVariables();

        $this->asteriskLogger->notice('Init');

        $this->agi->answer();
    }

    public function signalHandler($signo)
    {
        $this->asteriskLogger->notice("Got signal: $signo");
        $this->logger->info("Got signal: $signo");
    }

    public function errorHandler($type, $message, $file, $line)
    {
        $this->asteriskLogger->error("$message at $file:$line");
        $this->logger->error("$message at $file:$line");
    }

    public function shutdown()
    {
        $this->asteriskLogger->notice('Shutdown');
    }
}

$pagiClientOptions = array();
$pagiClient = PagiClient::getInstance($pagiClientOptions);
$pagiAppOptions = array(
    'pagiClient' => $pagiClient,
);
$pagiApp = new TeleAirport($pagiAppOptions);
$pagiApp->init();
$pagiApp->welcomeMessage();
$pagiApp->run();

?>