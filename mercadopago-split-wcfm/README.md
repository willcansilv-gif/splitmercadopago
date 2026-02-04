# Mercado Pago Split WCFM

Plugin WordPress para integração Mercado Pago Split Payments com WooCommerce e WCFM Marketplace, incluindo wizard de configuração guiada para marketplace.

## Requisitos
- WordPress 6.0+
- PHP 8.0+
- WooCommerce (última versão estável)
- WCFM Marketplace (última versão estável)
- Conta Mercado Pago com KYC completo

## Instalação
1. Envie a pasta `mercadopago-split-wcfm` para `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. O wizard será iniciado automaticamente após a ativação.

## Configuração (Wizard)
1. **Verificação de Ambiente**: valida versões e HTTPS.
2. **OAuth Mercado Pago**: informe Client ID e Client Secret e conecte a conta do marketplace.
3. **Comissão do Marketplace**: defina percentual e taxa fixa.
4. **Vendedores WCFM**: cada vendedor deve conectar sua conta Mercado Pago.
5. **Webhook**: configure o webhook no painel Mercado Pago e informe o segredo.
6. **Logs/Debug**: habilite logs e debug se necessário.
7. **Finalização**: habilita o gateway após validações.

## Sandbox vs Produção
Use credenciais de teste no ambiente sandbox e altere para produção após validar todo o fluxo. As credenciais são configuradas no wizard OAuth.

## Troubleshooting
- Verifique logs em `/wp-content/uploads/mercadopago-split-logs/`.
- Confirme se o webhook está ativo e com assinatura válida.
- Revise se vendedores conectaram suas contas via OAuth.

## Segurança
- Tokens são armazenados de forma criptografada quando possível.
- Logs são sanitizados para não expor segredos.
- Diretório de logs protegido com `.htaccess` e `index.html`.

## Changelog
### 1.0.0
- Lançamento inicial com OAuth, pagamentos com split e webhooks.
