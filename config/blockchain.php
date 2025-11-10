<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blockchain Mode
    |--------------------------------------------------------------------------
    |
    | Mode: 'ganache' for development, 'production' for mainnet/testnets
    |
    */

    'mode' => env('BLOCKCHAIN_MODE', 'ganache'),

    /*
    |--------------------------------------------------------------------------
    | Ganache Wallets (Development Only)
    |--------------------------------------------------------------------------
    |
    | Pre-configured wallets from Ganache for development
    | These are assigned automatically to new users in ganache mode
    |
    */

    'ganache_wallets' => [
        [
            'address' => '0x54A93cCF5B76Ed4A1311A76EBF06cF6fD9E8938E',
            'private_key' => '0x5aa14777bd9a1d6a71f9c2a0ca8323f2267a444daee256839ac4d3f0bd12f406',
        ],
        [
            'address' => '0xf00A1300F5C17B4294cc5ac50fA5510E9D5F25E8',
            'private_key' => '0x2769125112e959ebd0e7751868dfe9c562786bb49cdd9acce3b1faac7360c3eb',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blockchain Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines if blockchain functionality is enabled.
    | Set to false to disable all blockchain operations.
    |
    */

    'enabled' => env('BLOCKCHAIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | RPC URL
    |--------------------------------------------------------------------------
    |
    | The RPC URL of your Ethereum node (Ganache, Infura, Alchemy, etc.)
    |
    */

    'rpc_url' => env('BLOCKCHAIN_RPC_URL', 'http://127.0.0.1:7545'),

    /*
    |--------------------------------------------------------------------------
    | Chain ID
    |--------------------------------------------------------------------------
    |
    | The chain ID of the blockchain network
    | Ganache local: 1337 or 5777
    | Ethereum Mainnet: 1
    | Sepolia Testnet: 11155111
    |
    */

    'chain_id' => env('BLOCKCHAIN_CHAIN_ID', 5777),

    /*
    |--------------------------------------------------------------------------
    | Smart Contract Address
    |--------------------------------------------------------------------------
    |
    | The deployed address of the RentalContract smart contract
    |
    */

    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Backend wallet used for signing transactions
    |
    */

    'wallet_address' => env('BLOCKCHAIN_WALLET_ADDRESS'),

    'wallet_private_key' => env('BLOCKCHAIN_WALLET_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gas Configuration
    |--------------------------------------------------------------------------
    |
    | Default gas settings for transactions
    |
    */

    'gas_limit' => env('BLOCKCHAIN_GAS_LIMIT', 3000000),

    'gas_price' => env('BLOCKCHAIN_GAS_PRICE', '20000000000'), // 20 Gwei

    /*
    |--------------------------------------------------------------------------
    | Transaction Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for transaction confirmation
    |
    */

    'transaction_timeout' => env('BLOCKCHAIN_TX_TIMEOUT', 60),

];
