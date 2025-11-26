// Função auxiliar para formatar como moeda BRL
const formatarMoeda = (valor) => {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

function calcularFinanciamento() {
    // 1. Coleta e conversão de Inputs
    const valorImovel = parseFloat($('#valorImovel').val());
    const valorEntrada = parseFloat($('#valorEntrada').val());
    const taxaJurosAnual = parseFloat($('#taxaJurosAnual').val());
    const prazoMeses = parseInt($('#prazoMeses').val());

    if (isNaN(valorImovel) || isNaN(valorEntrada) || isNaN(taxaJurosAnual) || isNaN(prazoMeses) || prazoMeses <= 0) {
        alert("Por favor, preencha todos os campos com valores válidos.");
        return;
    }

    // 2. Cálculos de Base
    const principal = valorImovel - valorEntrada;
    const taxaMensal = (taxaJurosAnual / 100) / 12; // Taxa i
    const n = prazoMeses; // Número de períodos

    // 3. Fórmula da Tabela Price
    // P = Principal * [ i * (1 + i)^n ] / [ (1 + i)^n - 1 ]
    let parcelaFixa;
    
    // Evita divisão por zero se a taxa for zero
    if (taxaMensal === 0) {
        parcelaFixa = principal / n;
    } else {
        const fator = Math.pow(1 + taxaMensal, n);
        parcelaFixa = principal * (taxaMensal * fator) / (fator - 1);
    }

    // 4. Montar a Tabela de Amortização
    let saldoDevedor = principal;
    let totalJuros = 0;
    let tabelaHTML = '';

    for (let i = 1; i <= n; i++) {
        // Cálculo dos juros do mês
        const juros = saldoDevedor * taxaMensal;
        
        // Cálculo da amortização
        const amortizacao = parcelaFixa - juros;
        
        // Atualiza o saldo devedor
        saldoDevedor -= amortizacao;

        // Acumula juros
        totalJuros += juros;

        // Ajuste de arredondamento na última parcela para zerar o saldo
        if (i === n) {
            saldoDevedor = 0;
        }

        tabelaHTML += `
            <tr class="hover:bg-gray-700">
                <td class="p-2">${i}</td>
                <td class="p-2">${formatarMoeda(parcelaFixa)}</td>
                <td class="p-2 text-red-300">${formatarMoeda(juros)}</td>
                <td class="p-2 text-green-300">${formatarMoeda(amortizacao)}</td>
                <td class="p-2">${formatarMoeda(saldoDevedor)}</td>
            </tr>
        `;
    }

    // 5. Exibir Resultados
    const custoTotal = principal + totalJuros;

    $('#resPrincipal').text(formatarMoeda(principal));
    $('#resParcela').text(formatarMoeda(parcelaFixa));
    $('#resTotalJuros').text(formatarMoeda(totalJuros));
    $('#resCustoTotal').text(formatarMoeda(custoTotal));
    $('#tabelaAmortizacao').html(tabelaHTML);
    
    $('#resultado').removeClass('hidden');
}