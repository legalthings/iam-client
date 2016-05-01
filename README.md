Legal Things - LegalMail client
==================

[API documentation](http://docs.legalmail.apiary.io)

## Requirements

- [PHP](http://www.php.net) >= 5.5.0

_Required PHP extensions are marked by composer_

## Installation

The library can be installed using composer. Add the following to your `composer.json`:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/legalthings/legalmail-client"
        }
    ],
    require: {
        "legalthings/legalmail-client": "~0.1.0"
    }


## Usage

    LegalMail::send($template, $from, $to, $data, $attachments = []);

type   | parameter    | description
------ | ------------ | --------------- 
string | $template    | Reference of the template
array  | $from        | Associative array that contains one 'name' and 'email' property
array  | $to          | Associative array that contains multiple 'name' and 'email' properties
array  | $data        | Data to fill in the template
array  | $attachments | Associative array containing 'source' and 'filename' properties

_Note: It's only allowed to set a `from` address when sending a e-mail to the primary organization's contact
e-mail address._

### Configuration (do this only once)

    LegalThings\LegalMail::$url = App::config()->legalmail->url;

### Example - Send an e-mail to a person

    use LegalThings\LegalMail;

    $to = ['name' => 'John Doe', 'email' => 'john.doe@example.com'];
    $data = ['name' => 'John Doe', 'gender' => 'male'];
    LegalMail::send('my-template', null, $to, $data);

### Example - Handle a contact form which e-mails us

    use LegalThings\LegalMail;

    $from = ['name' => $_POST['name'], 'email' => $_POST['email']];
    $to = ['name' => 'LegalThings', 'email' => 'info@legalthings.net'];
    LegalMail::send('contact', $from, , $_POST);


## Testing

You may use the [Guzzle Mock Handler](http://guzzle.readthedocs.org/en/latest/testing.html#mock-handler) when performing
tests that include LegalMail.

    use LegalThings\LegalMail;
    use GuzzleHttp\Handler\MockHandler;
    use GuzzleHttp\HandlerStack;
    use GuzzleHttp\Psr7\Response;

    $mock = new MockHandler([
        new Response(204)
    ]);

    $handler = HandlerStack::create($mock);
    LegalMail::$guzzleOptions = ['handler' => $mock];
    
    // Request is intercepted
    LegalMail::send('my-template', null, 'john.doe@example.com', ['name' => 'John Doe', 'gender' => 'male']);
