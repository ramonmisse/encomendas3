
// Order management scripts
async function handleOrderErrors(response) {
    if (!response.ok) {
        const error = await response.json();
        console.error('Error:', error);
        alert('Erro ao processar pedido: ' + (error.details || error.error));
        return false;
    }
    return true;
}
