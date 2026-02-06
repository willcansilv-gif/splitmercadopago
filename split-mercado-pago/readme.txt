=== Split Mercado Pago WooCommerce ===
Contributors: split-mercado-pago
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0

Plugin para integração Mercado Pago Split Payments com WooCommerce, permitindo split real via application_fee.

== Descrição ==
Split Mercado Pago WooCommerce integra pagamentos com split real para marketplaces. Cada vendedor autoriza via OAuth e os pagamentos são criados com o access_token do vendedor, com a comissão do marketplace aplicada via application_fee.

== Instalação ==
1. Envie a pasta `split-mercado-pago` para `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Configure Client ID, Client Secret e Public Key em WooCommerce > Split Mercado Pago.
4. Vendedores devem conectar suas contas Mercado Pago no perfil.

== Configurações ==
- Client ID / Client Secret / Public Key
- Comissão percentual e fixa
- Modo sandbox
- Logs

== Logs ==
Os logs são gravados em `wp-content/uploads/split-mercado-pago-logs/`.

== Segurança ==
Tokens são armazenados de forma criptografada e logs sanitizados.
