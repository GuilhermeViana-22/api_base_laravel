<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Base da especificação OpenAPI: identificação, servidor, autenticação e as
 * tags que agrupam as rotas no Swagger UI.
 *
 * Esta classe não é instanciada nem chamada por ninguém — ela existe só para
 * carregar os atributos que o swagger-php lê ao montar `/api/docs.json`. O
 * resto da especificação está em App\OpenApi\Schemas (formatos de resposta) e
 * nos próprios controllers (uma rota por método).
 */
#[OA\Info(
    version: '1.0.0',
    title: 'API UNIVESP',
    description: <<<'TXT'
    API do site da UNIVESP e do painel que o alimenta.

    **Duas metades, uma base de dados.** As rotas públicas (`/api/posts`,
    `/api/home`, `/api/banners/...`) são abertas e só mostram o que está no ar;
    as rotas do painel (`/api/admin/...`) exigem token Bearer e enxergam
    também rascunhos, agendamentos e cadastros.

    **Autenticação.** `POST /api/auth/login` devolve `access_token`. Clique em
    *Authorize* aqui em cima, cole o token e as rotas do painel passam a
    responder. Conta nova entra por `register` → `verify-email`, que já devolve
    a sessão pronta.

    **Formato das respostas.** Todo recurso vem embrulhado em `data`. Listagens
    paginadas trazem também `links` e `meta`; algumas acrescentam blocos
    próprios (`counts`, `polos`) que a tela do painel usa.

    **Erros.** Validação responde 422 com `{ message, errors }`. Os erros de
    negócio da autenticação respondem `{ message, code }`, onde `code` é
    estável e serve para o front decidir o que fazer (a `message` é só texto
    para o usuário).
    TXT,
    contact: new OA\Contact(name: 'UNIVESP'),
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST.'/api',
    description: 'Servidor da API (definido por APP_URL)',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Token devolvido por `POST /auth/login` (ou por `verify-email`). Cole só o token: o `Bearer ` entra sozinho.',
    name: 'Authorization',
    in: 'header',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Autenticação', description: 'Cadastro, confirmação de e-mail, login e sessão.')]
#[OA\Tag(name: 'Site · Notícias', description: 'Leitura pública das notícias publicadas.')]
#[OA\Tag(name: 'Site · Banners', description: 'Banner (hero) das páginas do site.')]
#[OA\Tag(name: 'Site · Página inicial', description: 'Carrossel, blocos, contadores e depoimentos da home.')]
#[OA\Tag(name: 'Site · Navegação', description: 'Menu do cabeçalho e relação de rotas do site.')]
#[OA\Tag(name: 'Site · Cursos', description: 'Cursos no ar e a página de cada um.')]
#[OA\Tag(name: 'Painel · Notícias', description: 'CRUD das notícias, incluindo rascunhos e agendadas.')]
#[OA\Tag(name: 'Painel · Banners', description: 'Textos e foto de fundo dos banners.')]
#[OA\Tag(name: 'Painel · Carrossel', description: 'Slides do topo da página inicial: conteúdo, imagem e ordem.')]
#[OA\Tag(name: 'Painel · Página inicial', description: 'Blocos de chave fixa, contadores e depoimentos.')]
#[OA\Tag(name: 'Painel · Cursos', description: 'CRUD dos cursos: página, textos e ordem no menu do site.')]
#[OA\Tag(name: 'Painel · Usuários', description: 'Listagem das contas e troca de situação.')]
#[OA\Tag(name: 'Painel · Equipe', description: 'Quem entra no painel, papéis e a matriz de permissões.')]
#[OA\Tag(name: 'Painel · Configurações', description: 'Ajustes do CMS, a começar pelas páginas que aparecem no site.')]
#[OA\Tag(name: 'Painel · Uploads', description: 'Imagens inseridas no corpo do texto pelo editor.')]
final class ApiDoc {}
