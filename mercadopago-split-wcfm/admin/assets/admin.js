jQuery(function ($) {
  const form = $('[data-split-preview]');
  if (!form.length) {
    return;
  }

  const updatePreview = () => {
    const percent = parseFloat($('#commission_percent').val()) || 0;
    const fixed = parseFloat($('#commission_fixed').val()) || 0;
    const total = 100;
    let commission = total * (percent / 100) + fixed;
    commission = Math.max(0, Math.min(commission, total));
    const vendor = total - commission;

    form.find('.mpsw-split-preview p').eq(0).text(`Marketplace: R$ ${commission.toFixed(2).replace('.', ',')}`);
    form.find('.mpsw-split-preview p').eq(1).text(`Vendedor: R$ ${vendor.toFixed(2).replace('.', ',')}`);
  };

  $('#commission_percent, #commission_fixed').on('input', updatePreview);
});
