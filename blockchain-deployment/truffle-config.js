const HDWalletProvider = require('@truffle/hdwallet-provider');

module.exports = {
  networks: {
    development: {
      host: "192.168.100.9",
      port: 8545,
      network_id: "1337",
    },
    sepolia: {
      provider: () => new HDWalletProvider({
        privateKeys: ['fb3e4a9d61c237a16bf74a5e24b026d95d66b569d764cae08ee2220ea497ac1d'],
        providerOrUrl: 'https://eth-sepolia.g.alchemy.com/v2/30yzaPvHJstfK0QudRMqS'
      }),
      network_id: 11155111,
      gas: 3000000,
      gasPrice: 2000000000,
      confirmations: 2,
      timeoutBlocks: 200,
      skipDryRun: true
    },
  },

  compilers: {
    solc: {
      version: "0.8.10",
      settings: {
        optimizer: {
          enabled: false,
          runs: 200
        },
      }
    },
  }
};
