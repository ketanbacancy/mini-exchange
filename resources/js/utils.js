export const formatMoney = (value, decimals = 2) => {
    if (value === null || value === undefined) return '0.00';
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
};
