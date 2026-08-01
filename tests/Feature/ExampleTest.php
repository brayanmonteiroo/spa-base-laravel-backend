<?php

test('a aplicação retorna uma resposta de sucesso', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
