# e-Rede por Piggly

![Branch Lançamento](https://img.shields.io/badge/branch%2Frelease-v1.x.x-brightgreen?style=flat-square) ![Branch Desenvolvimento](https://img.shields.io/badge/branch%2Fdev-dev%20v1.x.x-orange?style=flat-square) ![Versão Atual](https://img.shields.io/badge/version-v1.0.0-brightgreen?style=flat-square) ![PHP](https://img.shields.io/badge/php-%5E7.2%20%7C%20%5E8.0-blue?style=flat-square) ![Software License](https://img.shields.io/badge/license-GPL%203.0-brightgreen?style=flat-square)

O melhor plugin para pagamentos via Cartão de Crédito e Cartão de Débito utilizando a plataforma e-Rede no Woocommerce. Veja todos os detalhes abaixo.

> Esse plugin não possuí fins lucrativos e não tem qualquer associação com a empresa Rede e a marca e-Rede, é desenvolvido pelo laboratório **Piggly** e faz a comunicação com os serviços prestados pela API da e-Rede.

**Não coletamos nenhum dado durante a sua experiência com o plugin**. Sempre atualize o seu plugin para continuar aproveitando todos os recursos e mantê-lo seguro. O código-fonte deste plugin é público e está disponível para todos no [GitHub](https://github.com/piggly-dev/woo-erede-por-piggly).

> Se você apreciar a função deste plugin e quiser apoiar este trabalho, sinta-se livre para fazer qualquer doação para a chave aleatória Pix `285fb964-0087-4a94-851a-5a161ed8888a` ❤.

> Não esqueça de deixar a sua avaliação sobre o plugin! Isso nos incentivará a lançar mais atualizações e continuar prestando um suporte de qualidade.

## Como funciona?

O plugin foi desenvolvido com suporte ao **WooCommerce 4+** e **WordPress 5+** e habilita o processo de comunicação entre a sua loja virtual e a API de pagamentos da e-Rede. Com isso, seus clientes poderão fazer compras no crédito e no débito de modo transparente e seguro, sem redirecionamentos.

## Requisitos

Antes de iniciar a integração e habilitar o plugin, será necessário gerar a chave de integração no portal da Rede para a sua conta credenciada com a e-Rede. Siga os seguintes passos:

* Acesse o portal Use Rede e realize o login;
* Entre no menu e-commerce e selecione a opção chave de integração;
* Clique em gerar chave de integração para obtê-la.
* Pronto! Chave de integração gerada.

Nas configurações do plugin, disponíveis em **e-Rede > Configurações**, insira o número de filiação e a chave de integração na tela principal. Posteriormente, você pode habilitar e configurações os pagamentos via crédito e débito.

### Homologação

Se você tem interesse de testar o plugin, mantenha o ambiente no modo **Teste** nas configurações do plugin, cadastre-se no [Portal dos Desenvolvedores](https://www.userede.com.br/desenvolvedores) para criar um PV e uma Chave de Integração de testes. Depois, faça as compras utilizando os cartões de teste disponibilizados pela e-Rede [clicando aqui](https://www.userede.com.br/desenvolvedores/pt/produto/e-Rede#tutorial-cartao).

## Como instalar?

### No diretório oficial do Wordpress

A página oficial do plugin pode ser encontrada em: [wordpress@erede-por-piggly](https://wordpress.org/plugins/erede-por-piggly/).

### No repositório do Github

Vá para [Releases](https://github.com/piggly-dev/woo-erede-por-piggly/releases) neste repositório e faça o download em `.zip` da versão mais recente.

Então, no **Painel Administrativo** do Wordpress, vá em `Plugins > Adicionar novo` e clique em `Upload plugin` no topo da página para enviar o arquivo `.zip`.

> Você precisará, posteriormente, ir até a pasta do plugin no terminal do seu servidor Web e executar o comando `composer install` caso escolha essa opção.

### Da origem

Você precisará do Git instalado para contruir da origem. Para completar os passos a seguir, você precisará abrir um terminal de comando. Clone o repositório:

`git clone https://github.com/piggly-dev/woo-erede-por-piggly.git`

> Não recomendamos este processo de instalação a não ser que você saiba o que está fazendo.

## Changelog

Veja o arquivo [CHANGELOG](./CHANGELOG.md) para informações sobre todas as mudanças no código.

## Desenvolvimento

Veja o arquivo [CONTRIBUTING](./CONTRIBUTING.md) para todas as informações a respeito das regras de conduta do desenvolvimento e organização do código.

## Créditos

- [Caique Araujo](https://github.com/caiquearaujo)
- [Piggly Lab](https://github.com/piggly-dev)
- [Todos os desenvolvedores](../../contributors)

## Licenciamento

GNU GENERAL PUBLIC LICENSE (GNU v3). Veja [LICENSE](./LICENSE).