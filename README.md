# Hermes

Essa Lib é responsável por encapsular a conexão com gerenciadores de filas. Focando no uso com [RabbitMQ](http://www.rabbitmq.com/).

** Dependências: PHP 5.3 ** Devido ao uso de namespaces.

** Dependências: bcmath e mbstring ** Devido ao uso do [php-amqplib](https://github.com/php-amqplib/php-amqplib/) para conexão através do protocolo AMQP 0-9-1.

## Instalação

Primeiro, vamos adicionar a dependência e o repositório do hermes no nosso arquivo composer.json:

```json
{
    "require": {
        "eduzz/hermes": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@bitbucket.org:eduzz/hermes.git"
        }
    ]
}
```

Após, vamos rodar o comando

```
composer dump-autoload
```

Para atualizar o cache do composer

```
composer install
```

Para instalar as dependência e o hermes

PS: É preciso verificar se você está com a chave conectada ao bitbucket no shell onde vai instalar o hermes.

## Instalação em projeto Laravel

O próximo passo é registrar o Hermes na lista de service providers, dentro do seu config/app.php, adicione o Hermes na sua lista de providers e adiciona também a facade do Hermes na lista de aliases.

```php
'providers' => [
    // ...

    Eduzz\Hermes\HermesLaravelServiceProvider::class,
],
```

```php
'aliases' => [
    // ...

    'Hermes' => Eduzz\Hermes\Facades\HermesFacade::class,
],
```

Precisamos limpar nosso cache, atualizar nossos pacotes e publicar a configuração do hermes:

```bash
php artisan cache:config
composer update
php artisan vendor:publish --tag="config"
```

Se tudo ocorreu bem, a seguinte mensagem sera exibida:

```bash
Copied File [/vendor/eduzz/src/Config/hermes.php] To [/config/hermes.php]
```

Então, é necessário configurar o hermes, no arquivo config/hermes.php, na variável connection, é onde devem ficar os seus dados de conexão, por exemplo:

```php
<?php

return [
    'connection' =>  [
        'host' => env('HERMES_HOST', '127.0.0.1'),
        'port' => env('HERMES_PORT', 5672),
        'username' => env('HERMES_USERNAME', 'guest'),
        'password' => env('HERMES_PASSWORD', 'guest'),
        'vhost' => env('HERMES_VHOST', '/'),
        'connection_name' => env('HERMES_CONNECTION_NAME', '/'),
    ]
];
```

### Instalação em projeto Lumen

Para instalação em projeto lumen, é preciso criar o arquivo de configuração na mão, vamos adicionar um arquivo chamado hermes.php na pasta config com o seguinte conteúdo:

```php
<?php

return [
    'connection' =>  [
        'host' => env('HERMES_HOST', '127.0.0.1'),
        'port' => env('HERMES_PORT', 5672),
        'username' => env('HERMES_USERNAME', 'guest'),
        'password' => env('HERMES_PASSWORD', 'guest'),
        'vhost' => env('HERMES_VHOST', '/'),
        'connection_name' => env('HERMES_CONNECTION_NAME', '/'),
    ]
];
```

Vamos também adicionar nosso service provider no register, então na pasta bootstrap/app.php, procure pela linha que faz os registros e adicione:

```php
<?php
// ...
$app->register(Eduzz\Hermes\HermesLaravelServiceProvider::class);
// ...
```

Adicione também a chamada para a configuração do hermes no bootstrap/app.php:

```php
<?php
$app->configure('hermes');

return $app;
```

## Instalação em um projeto sem framework Illuminate

Para utilizar o Hermes sem laravel/lumen, é necessário setar as configurações na mão, exemplo:

```php
<?php

require 'vendor/autoload.php';

use Eduzz\Hermes\Hermes;

$hermes = new Hermes([
	'host'  =>  '127.0.0.1',
	'port' => 5672,
	'username' => 'guest',
	'password' => 'guest',
	'vhost' => '/'
]);

```

Há um método chamado setConfig, onde, as configurações podem ser atualizadas, caso você não queira passar pelo construtor.

# Usage

O Hermes possui 2 interações com o rabbitMQ, declarar filas, consumir as filas e enviar mensagens.

## Enviando mensagens

### Definindo as mensagens

Para definir uma nova mensagem, é necessário estender nossa mensagem abstrata:

```php
<?php

namespace App\HermesMessages;

use Eduzz\Hermes\Message\AbstractMessage;

class ExampleMessage extends AbstractMessage
{
    public function __construct($id, $message)
    {
        parent::__construct(
            // Sua routing Key
            'app.module.action',

            // Seu payload
            ['id' => $id, 'message' => $message]
        );
    }
}

```
Dentro da sua mensagem, não importa a lógica, você pode passar qualquer dado, porém, a mensagem do hermes precisa receber sempre 1 string que é a routingKey, e o segundo parâmetro um array de dados quaisquer.

### Enviando mensagens em Laravel/Lumen

Vamos utilizar a injeção de dependência do laravel para instanciar o Hermes já pegando as configurações do arquivo config/hermes.php, exemplo de um controller do laravel utilizando o hermes:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Eduzz\Hermes\Hermes;

use App\HermesMessages\ExampleMessage;

class Controller extends BaseController
{
    private $hermes;

    public function __construct(Hermes $hermes) {
        $this->hermes = $hermes;

        parent::__construct();
    }

    public function method() {
        // Sua lógica aqui

        $this->hermes->publish(
            new ExampleMessage(5, 'Hello world'),
            'my_exchange' // Paramêtro opcional, qual exchange a mensagem deve ir
        );
    }
}

```

O segundo paramêtro do método publish (exchange) é opcional, ele é a exchange para onde a mensagem vai, por padrão, é utilizada uma exchange com o nome 'eduzz'.

### Enviando mensagens com Hermes sem Framework

A lógica é a mesma, porém, passando as configurações no construtor ou através do setConfig, exemplo:
return array(
    'connection' =>  array(
        'host' => env('HERMES_HOST', '127.0.0.1'),
        'port' => env('HERMES_PORT', 5672),
        'username' => env('HERMES_USERNAME', 'guest'),
        'password' => env('HERMES_PASSWORD', 'guest'),
        'vhost' => env('HERMES_VHOST', '/').
        'connection_name' => env('HERMES_CONNECTION_NAME', '/'),
    )
);
```php
<?php

$hermes->publish(
    new Message(
        [
            "id" => 123,
            "name" => "John Doe",
            "age" => 18
        ],
        'my_exchange' // Paramêtro opcional, qual exchange a mensagem deve ir
    );
);

```

## Declarando e consumindo filas

### Adicionando novas filas

Para consumir uma fila no modo de trabalho das nossas exchange (topic), é preciso declarar uma fila e fazer um 'bind', um bind é ligar uma fila a uma routingKey, ou seja, dizer que, mensagens que possuam um certa routingKey, irão para uma fila que criamos.

![Esquema de topic em filas](https://www.rabbitmq.com/img/tutorials/python-five.png)

Vamos utilziar o Hermes para declarar uma fila e fazer um bind.

```php
<?php

$queueName = $hermes->addQueue(
        'queue_name', // Se for uma string vazia, irá criar um nome aleatório
        true, // Ativa o nack e cria uma fila adicional chamada queue_name.nack, o paramêtro é opcional e o default é a fila ativa
        true // Parâmetro opcional de fila durável ou não, o padrão é durável
    )
        ->bind(
            'my.routing.key',
            null, // Parâmetro opcional de nome da fila, se for null, irá pegar o nome da última fila criada
            'custom_exchange'; // Parâmetro opcional exchange, se não for passado, irá pegar a exchange default 'eduzz'
        )
        ->getLastQueueCreated(); // Retorna o nome da última fila criada

```

### Consumindo uma fila

Para consumir uma fila, precisamos adicionar um callback a uma fila que criamos, e então, chamar o start do Hermes que vai dar ínicio ao processamento da fila.

```php
<?php

$hermes->addListenerTo(
    'queue_name',
    function($msg) {
        echo json_encode($msg->body);
    },
    true // Ativa ou não a opção de errorHandling do Hermes
);

$hermes->start();
```

Caso o errorHandling do Hermes esteja ativo, é necessário que a fila conectada possua o nack ativado, as exceções não capturadas dentro do callback serão tratadas e o conteúdo da mensagem será enviado para a fila de nack.

Caso o nack esteja ativado e o errorHandling esteja desativado, é necessário, dentro do seu callback, utilizar os métodos ack e nack, exemplo:

```php
<?php

$hermes->addListenerTo(
	'queue_name',
	function($msg) use ($hermes) {
		try {
			echo json_encode($msg->body);

			return $hermes->consumer()->ack($msg);
		 catch(\Exception $e) {
			return $hermes->consumer()->nack($msg);
		}
	},
	false // Error handling desativado
);

$hermes->start();
```
