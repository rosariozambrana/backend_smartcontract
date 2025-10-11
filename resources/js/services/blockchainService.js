import Web3 from 'web3';
import PropietarioContract from './PropietarioContract.json';

let web3;
let contract;
let account;
let cantidad;

export const initBlockchain = async () => {
    if (window.ethereum) {
        web3 = new Web3(window.ethereum);
        await window.ethereum.request({ method: 'eth_requestAccounts' });
    } else if(web3) {
        web3 = new Web3(web3.currentProvider);
    }else{
        console.error('Por favor, instala MetaMask.');
        return;
    }

    const networkId = await web3.eth.net.getId();
    const deployedNetwork = PropietarioContract.networks[networkId];
    if (deployedNetwork) {
        contract = new web3.eth.Contract(
            PropietarioContract.abi,
            deployedNetwork.address
        );
    } else {
        console.error('Contrato no desplegado en esta red.');
    }
};
export const getAccount = async () => {
    const accounts = await web3.eth.getAccounts();
    account = accounts[0];
    return account;
};
export const createPropietario = async (nombre, numId, userId) => {
    if(account === undefined){
        console.error('No hay cuenta conectada.');
        return;
    }else{
        console.log('Cuenta conectada:', account);
    }
    const result = contract.methods.createPropietario(nombre, numId, userId).send({ from: account });
    result.on('transactionHash', (hash) => {
        console.log('Transacción enviada:', hash);
    });
    console.log('result', result);
};
export const getPropietarioCounter = async () => {
    cantidad = await contract.methods.propietarioCounter().call();
    return cantidad;
}
export const getPropietario = async (id) => {
    const result = await contract.methods.propietarios(id).call();
    return result;
}
