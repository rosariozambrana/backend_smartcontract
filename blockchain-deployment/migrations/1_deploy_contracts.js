const RentalContract = artifacts.require("RentalContract");

module.exports = function(deployer) {
  deployer.deploy(RentalContract);
};
