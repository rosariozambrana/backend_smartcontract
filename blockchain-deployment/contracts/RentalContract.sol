// SPDX-License-Identifier: MIT
pragma solidity ^0.8.10;

/**
 * @title RentalContract
 * @dev Smart contract for managing rental agreements
 */
contract RentalContract {
    // Contract states
    enum ContractState { Pending, Approved, Active, Terminated, Expired }

    // Contract structure
    struct RentalAgreement {
        uint256 contractId;
        address landlord;
        address tenant;
        uint256 propertyId;
        uint256 rentAmount;
        uint256 depositAmount;
        uint256 startDate;
        uint256 endDate;
        uint256 lastPaymentDate;
        ContractState state;
        string termsHash;
    }

    mapping(uint256 => RentalAgreement) public rentalAgreements;
    address public owner;

    event ContractCreated(uint256 indexed contractId, address indexed landlord, address indexed tenant);
    event ContractApproved(uint256 indexed contractId);
    event ContractActivated(uint256 indexed contractId);
    event PaymentReceived(uint256 indexed contractId, uint256 amount, uint256 timestamp);
    event ContractTerminated(uint256 indexed contractId, string reason);
    event ContractExpired(uint256 indexed contractId);

    constructor() {
        owner = msg.sender;
    }

    function createContract(
        uint256 _contractId,
        address _landlord,
        address _tenant,
        uint256 _propertyId,
        uint256 _rentAmount,
        uint256 _depositAmount,
        uint256 _startDate,
        uint256 _endDate,
        string memory _termsHash
    ) public {
        require(_contractId > 0, "El ID del contrato debe ser positivo");
        require(rentalAgreements[_contractId].contractId == 0, "El ID del contrato ya existe");
        require(_landlord != address(0), "Direccion de propietario no valida");
        require(_tenant != address(0), "Direccion de inquilino no valida");
        require(_startDate < _endDate, "La fecha de finalizacion debe ser posterior a la fecha de inicio");
        require(_rentAmount > 0, "El importe del alquiler debe ser positivo");

        RentalAgreement memory newAgreement = RentalAgreement({
            contractId: _contractId,
            landlord: _landlord,
            tenant: _tenant,
            propertyId: _propertyId,
            rentAmount: _rentAmount,
            depositAmount: _depositAmount,
            startDate: _startDate,
            endDate: _endDate,
            lastPaymentDate: 0,
            state: ContractState.Pending,
            termsHash: _termsHash
        });

        rentalAgreements[_contractId] = newAgreement;

        emit ContractCreated(_contractId, _landlord, _tenant);
    }

    function approveContract(uint256 _contractId) public {
        RentalAgreement storage agreement = rentalAgreements[_contractId];

        require(agreement.contractId != 0, "El contrato no existe");
        require(agreement.state == ContractState.Pending, "El contrato no esta en estado pendiente");
        require(msg.sender == agreement.tenant, "Solo el inquilino puede aprobar el contrato.");

        agreement.state = ContractState.Approved;

        emit ContractApproved(_contractId);
    }

    function makePayment(uint256 _contractId) public payable {
        RentalAgreement storage agreement = rentalAgreements[_contractId];

        require(agreement.contractId != 0, "El contrato no existe");
        require(agreement.state == ContractState.Approved || agreement.state == ContractState.Active,
                "El contrato debe estar aprobado o activo");
        require(msg.sender == agreement.tenant, "Solo el inquilino puede hacer pagos");

        if (agreement.state == ContractState.Approved) {
            require(msg.value >= agreement.rentAmount + agreement.depositAmount,
                    "El primer pago debe incluir el deposito y el primer mes de alquiler.");

            agreement.state = ContractState.Active;
            emit ContractActivated(_contractId);
        } else {
            require(msg.value >= agreement.rentAmount, "El pago debe ser al menos el monto del alquiler.");
        }

        payable(agreement.landlord).transfer(msg.value);
        agreement.lastPaymentDate = block.timestamp;

        emit PaymentReceived(_contractId, msg.value, block.timestamp);
    }

    function terminateContract(uint256 _contractId, string memory _reason) public {
        RentalAgreement storage agreement = rentalAgreements[_contractId];

        require(agreement.contractId != 0, "El contrato no existe");
        require(agreement.state == ContractState.Active, "El contrato debe estar activo.");
        require(msg.sender == agreement.landlord || msg.sender == agreement.tenant,
                "Solo el propietario o el inquilino pueden rescindir el contrato");

        agreement.state = ContractState.Terminated;

        emit ContractTerminated(_contractId, _reason);
    }

    function checkExpiration(uint256 _contractId) public {
        RentalAgreement storage agreement = rentalAgreements[_contractId];

        require(agreement.contractId != 0, "El contrato no existe");
        require(agreement.state == ContractState.Active, "El contrato debe estar activo.");

        if (block.timestamp > agreement.endDate) {
            agreement.state = ContractState.Expired;
            emit ContractExpired(_contractId);
        }
    }

    function getContractDetails(uint256 _contractId) public view returns (
        address landlord,
        address tenant,
        uint256 propertyId,
        uint256 rentAmount,
        uint256 depositAmount,
        uint256 startDate,
        uint256 endDate,
        uint256 lastPaymentDate,
        ContractState state,
        string memory termsHash
    ) {
        RentalAgreement memory agreement = rentalAgreements[_contractId];
        require(agreement.contractId != 0, "El contrato no existe");

        return (
            agreement.landlord,
            agreement.tenant,
            agreement.propertyId,
            agreement.rentAmount,
            agreement.depositAmount,
            agreement.startDate,
            agreement.endDate,
            agreement.lastPaymentDate,
            agreement.state,
            agreement.termsHash
        );
    }
}
