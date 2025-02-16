// Configuration
const API_URL = 'http://localhost:8000/api';
let authToken = null;

// DOM Elements
const loginSection = document.getElementById('loginSection');
const dashboardSection = document.getElementById('dashboardSection');
const loginForm = document.getElementById('loginForm');
const transferForm = document.getElementById('transferForm');
const userBalance = document.getElementById('userBalance');
const transactionsList = document.getElementById('transactionsList');

// Event Listeners
loginForm.addEventListener('submit', handleLogin);
transferForm.addEventListener('submit', handleTransfer);

// Check if user is already logged in
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('authToken');
    if (token) {
        authToken = token;
        showDashboard();
    }
});

// Authentication Functions
async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
        const response = await fetch(`${API_URL}/login_check`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: email,
                password: password
            })
        });

        if (!response.ok) {
            throw new Error('Échec de la connexion');
        }

        const data = await response.json();
        authToken = data.token;
        localStorage.setItem('authToken', authToken);
        showDashboard();
    } catch (error) {
        alert('Erreur de connexion: ' + error.message);
    }
}

function loginWithGoogle() {
    window.location.href = `${API_URL}/connect/google`;
}

function logout() {
    authToken = null;
    localStorage.removeItem('authToken');
    showLogin();
}

// Dashboard Functions
async function showDashboard() {
    loginSection.classList.add('d-none');
    dashboardSection.classList.remove('d-none');
    await Promise.all([
        loadUserInfo(),
        loadTransactions()
    ]);
}

function showLogin() {
    dashboardSection.classList.add('d-none');
    loginSection.classList.remove('d-none');
}

async function loadUserInfo() {
    try {
        const response = await fetch(`${API_URL}/users/me`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error('Erreur de chargement du profil');

        const user = await response.json();
        userBalance.textContent = `${user.balance} €`;
    } catch (error) {
        console.error('Erreur:', error);
    }
}

async function loadTransactions() {
    try {
        const response = await fetch(`${API_URL}/transactions`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (!response.ok) throw new Error('Erreur de chargement des transactions');

        const transactions = await response.json();
        displayTransactions(transactions);
    } catch (error) {
        console.error('Erreur:', error);
    }
}

function displayTransactions(transactions) {
    transactionsList.innerHTML = '';
    transactions.forEach(transaction => {
        const element = document.createElement('div');
        element.className = 'list-group-item transaction-item';
        const amountClass = transaction.type === 'CREDIT' ? 'positive' : 'negative';
        element.innerHTML = `
            <div>
                <strong>${transaction.type}</strong><br>
                <small>${new Date(transaction.createdAt).toLocaleDateString()}</small>
            </div>
            <span class="transaction-amount ${amountClass}">
                ${transaction.type === 'CREDIT' ? '+' : '-'}${transaction.amount} €
            </span>
        `;
        transactionsList.appendChild(element);
    });
}

async function handleTransfer(e) {
    e.preventDefault();
    const recipientPhone = document.getElementById('recipientPhone').value;
    const amount = document.getElementById('amount').value;

    try {
        const response = await fetch(`${API_URL}/transactions`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                recipientPhone,
                amount: parseFloat(amount),
                type: 'TRANSFER'
            })
        });

        if (!response.ok) throw new Error('Erreur lors du transfert');

        alert('Transfert effectué avec succès');
        transferForm.reset();
        await Promise.all([
            loadUserInfo(),
            loadTransactions()
        ]);
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
}

// Error Handling
function handleError(error) {
    console.error('Erreur:', error);
    if (error.status === 401) {
        logout();
    }
    alert(error.message || 'Une erreur est survenue');
}
