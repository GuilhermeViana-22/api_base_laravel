# Arquivos dos seeders

Esta pasta espelha o disco `public` do Laravel (`storage/app/public/`).

Exemplo:

- aqui: `database/seeders/arquivos/provao/arquivos/tutorial-matricula-provao-paulista.pdf`
- no disco: `storage/app/public/provao/arquivos/tutorial-matricula-provao-paulista.pdf`
- no site: `/storage/provao/arquivos/tutorial-matricula-provao-paulista.pdf`

Regras:

- Use a mesma pasta que o painel usa para aquele conteúdo (a constante `*_DIR`
  do serviço, ex.: `ProvaoService::FILE_DIR = 'provao/arquivos'`).
- Nome do arquivo em minúsculas, com hífens, sem espaço nem acento.
- No seeder, use o trait `Database\Seeders\Concerns\CopiaArquivosParaStorage`:
  `$caminho = $this->copiarParaStorage('provao/arquivos/arquivo.pdf');` e grave
  `$caminho` no banco.
- Nada aqui é servido direto: o site lê a cópia do disco `public`, que o
  painel pode trocar ou apagar sem mexer no original.
