# Guia de Desenvolvimento

Leia e entenda o guia de contribuição antes de criar uma *issue* ou *pull request*.

> Por enquanto, apenas contribuições da equipe de desenvolvimento **Piggly** são aceitas.

## Etiqueta

Para manter a linearidade e consistência do código é muito importante seguir as regras pré-definidas pela equipe de desenvolvimento e disposta nesse documento.

## Branches

Somente os três branches abaixo serão permanentes:

* `main` é o branch intocável;
* `dev` é o branch de desenvolvimento da versão mais recente;

Os branches temporários são:

* `hotfix/<id>` envia `PATCHS` de correção para a última versão estável, pull requests devem ser criadas a partir do `main` e mescladas com o `main` e o `dev`;
* `feature/<nome>` cria novos recursos que mantém a compatibilidade com a última versão estável, são criados e enviados a partir do branch `dev` e respeirando o versionamento do branch, sem excessões;
* `release/<versão>` finaliza uma série de recursos para uma nova versão estável a partir do `dev` que deve ser refletida para o `main` incluindo a tag de versionamento;
* `bugfix/<id>` envia `PATCHS` de correção para o `release` antes de ser refletido para os demais branches.

## Como funciona?

### Atualização para a versão atual

Primeiro, dê um **fork** neste repositório. Então, no branch `dev`, crie um novo branch `feature/<nome>`:

```bash
# -> Certifique-se de que você está no ramo de desenvolvimento
git checkout dev
# <- Puxe o branch de desenvolvimento antes de criar um novo branch
git pull origin dev
# -> Crie um novo branch do tipo feature onde <nome> é um nome que identifica o seu recurso, quando for mais de um recurso, adicione a data no formato YmdHis-{id} ao invés do nome.
git checkout -b feature/<nome>
```

No branch `feature/<nome>` você pode fazer quantos `commits` forem necessários necessário:

```bash
# == Para que as coisas funcionem bem, sempre faça commits, não se preocupe com eles, apenas organize-se, faremos um squash quando a pull request for aceita
git add -A
git commit -m "<mensagem>"
```

> O ideal é que sejam feitos commits a cada nova peça de código finalizada.

Depois que seu trabalho estiver concluído, dê um **push** de `feature/<nome>` para seu repositório de origem e faça uma pull request a partir dele.

### Para correção de bugs

Caso sua pull request vise concertar um bug, ao invés do branch `feature` deve ser criado um branch de `hotfix/<id>` a partir do `main` ou da versão ativa que deve ser corrigida.

## Testes

Esta biblioteca usa o **PHPUnit**. Realizamos testes de todas as classes principais desta aplicação.

```bash
vendor /bin/phpunit
```

Você deve sempre executar testes com todas as versões do PHP atualmente compatíveis em `composer.json`. Por exemplo:

```bash
php7.2 vendor /bin/phpunit
php7.3 vendor /bin/phpunit
php7.4 vendor /bin/phpunit
php8.0 vendor /bin/phpunit
```

**Bom desenvolvimento**!