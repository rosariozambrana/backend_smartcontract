module.exports = {
  networks: {
    development: {
      host: "192.168.100.9",
      port: 8545,
      network_id: "1337",
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
