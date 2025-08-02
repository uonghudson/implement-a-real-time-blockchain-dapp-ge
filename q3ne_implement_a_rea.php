<?php

// Configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'q3ne_blockchain';

// Connect to database
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Blockchain class
class Blockchain {
    private $chain;
    private $pending_transactions;

    public function __construct() {
        $this->chain = array();
        $this->pending_transactions = array();
        $this->create_genesis_block();
    }

    public function create_genesis_block() {
        $block = array(
            'index' => 0,
            'previous_hash' => '0',
            'timestamp' => time(),
            'transactions' => array(),
            'hash' => $this->generate_hash(0, '0', time(), array())
        );
        array_push($this->chain, $block);
    }

    public function generate_hash($index, $previous_hash, $timestamp, $transactions) {
        $data = $index . $previous_hash . $timestamp . serialize($transactions);
        return hash('sha256', $data);
    }

    public function create_new_transaction($sender, $recipient, $amount) {
        $transaction = array(
            'sender' => $sender,
            'recipient' => $recipient,
            'amount' => $amount
        );
        array_push($this->pending_transactions, $transaction);
    }

    public function mine_pending_transactions($miner) {
        if(count($this->pending_transactions) < 1) {
            echo "No transactions to mine.\n";
            return;
        }

        $new_block = array(
            'index' => count($this->chain),
            'previous_hash' => $this->chain[count($this->chain)-1]['hash'],
            'timestamp' => time(),
            'transactions' => $this->pending_transactions,
            'hash' => $this->generate_hash(count($this->chain), $this->chain[count($this->chain)-1]['hash'], time(), $this->pending_transactions)
        );

        array_push($this->chain, $new_block);
        $this->pending_transactions = array();
        $reward_transaction = array(
            'sender' => 'network',
            'recipient' => $miner,
            'amount' => 1
        );
        array_push($this->pending_transactions, $reward_transaction);
    }

    public function get_chain() {
        return $this->chain;
    }
}

// dApp generator class
class dAppGenerator {
    private $blockchain;
    private $conn;

    public function __construct($blockchain, $conn) {
        $this->blockchain = $blockchain;
        $this->conn = $conn;
    }

    public function generate_dApp($dapp_name, $creator, $description) {
        $query = "INSERT INTO dapps (name, creator, description) VALUES ('$dapp_name', '$creator', '$description')";
        $this->conn->query($query);

        $dapp_id = $this->conn->insert_id;

        $query = "CREATE TABLE dapp$dapp_id (id INT AUTO_INCREMENT, transaction_id INT, PRIMARY KEY (id))";
        $this->conn->query($query);

        $this->blockchain->create_new_transaction('network', $creator, 10);
        $this->blockchain->mine_pending_transactions($creator);

        return $dapp_id;
    }

    public function get_dApp($dapp_id) {
        $query = "SELECT * FROM dapps WHERE id = $dapp_id";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
}

// Create blockchain instance
$blockchain = new Blockchain();

// Create dApp generator instance
$dApp_generator = new dAppGenerator($blockchain, $conn);

// Generate a new dApp
$dapp_id = $dApp_generator->generate_dApp('My dApp', 'Alice', 'This is my dApp');

// Get the generated dApp
$dapp = $dApp_generator->get_dApp($dapp_id);

print_r($dapp);

?>