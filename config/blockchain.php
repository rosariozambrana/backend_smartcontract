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
        [
            'address' => '0x326aC7010D1CEaFDb6Fa66A4C3F767F209863cff',
            'private_key' => '0x9a98c1ad9c41c7dea3a46a587faab462f004859c7f613cf076e0f7bffd03b849',
        ],
        [
            'address' => '0x05FDFca02C697d4b6768e785725fc5e5Fe13724b',
            'private_key' => '0x64cf7897efe735553eb2659fc196e9ceea0c8ab4b6bcf4d45f664850f5c8b427',
        ],
        [
            'address' => '0xeE167D5dE78590545B0C418C88902f7Ad97e42C2',
            'private_key' => '0x6ba823de09bc3ea6e9b9a589b7f9cb2ea0e926bea61ec7a4cb64ce0bb679a1ee',
        ],
        [
            'address' => '0x92Ba8D64f01fc63C9A6d786683Bd930eFFA54057',
            'private_key' => '0x003ed02a5c9792229bac42d9895208d6a74c5a46f3a4643899c9e406443883b8',
        ],
        [
            'address' => '0x3b8A6103d47212E04F6485Ef1Fb8DdD819A8684f',
            'private_key' => '0x7f6164ae81161fe650117ad0f451701caa93782aed8243d58df9b47bd4f43ca1',
        ],
        [
            'address' => '0x3f0A571DEDeF900f44B214bBf0C2a248C10E248f',
            'private_key' => '0xf6f6355f67b3c828549cc824be56a7ac7b5d59489d50196789fedc1ee1d2f338',
        ],
        [
            'address' => '0x79Fa14a92FBAC2E982C8FEFe4d922a65AA348990',
            'private_key' => '0x16fa2d7d1c501104b674476fde6a068fc2a84ee9f67cc196f605a587b4f49719',
        ],
        [
            'address' => '0xc208C92bB9B91c9CEBDf142Ca745573c652da7Aa',
            'private_key' => '0xd10f8608883a8912f6d9b9e00c0aa2b378145b1e391e2a3300de1e90d386d145',
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
