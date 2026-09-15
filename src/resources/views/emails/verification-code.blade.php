{{--
    Código de verificação do cadastro (HTML).
    Layout em tabelas e CSS inline: é o que Gmail/Outlook renderizam de forma confiável.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Seu código de verificação</title>
</head>
<body style="margin:0; padding:0; background-color:#ebf1f2; font-family:Arial, Helvetica, sans-serif; color:#172833;">
    {{-- Pré-visualização na caixa de entrada --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Use o código {{ $code }} para confirmar seu e-mail. Ele expira em {{ $ttlMinutes }} minutos.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ebf1f2;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px; background-color:#ffffff; border-radius:12px; overflow:hidden;">
                    {{-- Faixa da marca --}}
                    <tr>
                        <td style="background-color:#d13239; height:6px; line-height:6px; font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:32px 32px 8px;">
                            <p style="margin:0; font-size:26px; font-weight:bold; letter-spacing:1px; color:#d13239;">UNIVESP</p>
                            <p style="margin:4px 0 0; font-size:12px; color:#808285; text-transform:uppercase; letter-spacing:2px;">Área Restrita</p>
                        </td>
                    </tr>

                    {{-- Mensagem --}}
                    <tr>
                        <td style="padding:24px 32px 0;">
                            <h1 style="margin:0 0 12px; font-size:20px; line-height:28px; color:#172833;">Olá, {{ $name }}!</h1>
                            <p style="margin:0; font-size:15px; line-height:24px; color:#354551;">
                                Recebemos seu cadastro. Para confirmar que este e-mail é seu, digite o código abaixo na tela de verificação:
                            </p>
                        </td>
                    </tr>

                    {{-- Código --}}
                    <tr>
                        <td align="center" style="padding:28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="background-color:#f6f8f9; border:1px solid #e5e5e5; border-radius:10px; padding:18px 28px;">
                                        <span style="font-family:'Courier New', Courier, monospace; font-size:36px; font-weight:bold; letter-spacing:10px; color:#172833;">{{ $code }}</span>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:14px 0 0; font-size:13px; color:#d13239; font-weight:bold;">
                                Este código expira em {{ $ttlMinutes }} minutos.
                            </p>
                        </td>
                    </tr>

                    {{-- Segurança --}}
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <p style="margin:0; padding-top:20px; border-top:1px solid #e5e5e5; font-size:13px; line-height:20px; color:#808285;">
                                Não compartilhe este código com ninguém. A equipe da Univesp nunca pede o código por telefone, WhatsApp ou e-mail.
                                Se você não fez este cadastro, pode ignorar esta mensagem com segurança.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:20px 0 0; font-size:12px; color:#808285;">
                    Univesp — Universidade Virtual do Estado de São Paulo<br>
                    Mensagem automática, não é preciso responder.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
