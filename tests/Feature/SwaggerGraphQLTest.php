<?php

use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const GQL_KEY = '102022400337';

// ─────────────────────────────────────────────────────────────────────────────
// Swagger / OpenAPI
// ─────────────────────────────────────────────────────────────────────────────

test('Swagger UI is accessible at /api/documentation', function () {
    $this->get('/api/documentation')
         ->assertStatus(200);
});

test('OpenAPI JSON spec is accessible at /docs/api-docs.json', function () {
    $this->get('/docs/api-docs.json')
         ->assertStatus(200)
         ->assertHeader('Content-Type', 'application/json');
});

// ─────────────────────────────────────────────────────────────────────────────
// GraphQL
// ─────────────────────────────────────────────────────────────────────────────

test('GraphQL introspection query responds at /graphql', function () {
    $this->postJson('/graphql', [
        'query' => '{ __schema { queryType { name } } }',
    ])->assertStatus(200)
      ->assertJsonPath('data.__schema.queryType.name', 'Query');
});

test('GraphQL loans query returns an array', function () {
    Loan::factory()->count(3)->create();

    $response = $this->postJson('/graphql', [
        'query' => '{ loans { id applicant_name applicant_nim amount tenor_months status } }',
    ])->assertStatus(200)
      ->assertJsonStructure(['data' => ['loans']]);

    expect($response->json('data.loans'))->toHaveCount(3);
});

test('GraphQL loans query supports status filter', function () {
    Loan::factory()->count(2)->create(['status' => 'approved']);
    Loan::factory()->count(1)->create(['status' => 'pending']);

    $response = $this->postJson('/graphql', [
        'query' => '{ loans(status: "approved") { id status } }',
    ])->assertStatus(200);

    $loans = $response->json('data.loans');
    expect($loans)->toHaveCount(2);
    expect(collect($loans)->every(fn ($l) => $l['status'] === 'approved'))->toBeTrue();
});

test('GraphQL loan query returns single loan by ID', function () {
    $loan = Loan::factory()->create();

    $this->postJson('/graphql', [
        'query' => "{ loan(id: \"{$loan->id}\") { id applicant_name applicant_nim amount tenor_months purpose status } }",
    ])->assertStatus(200)
      ->assertJsonPath('data.loan.id', $loan->id)
      ->assertJsonPath('data.loan.applicant_nim', $loan->applicant_nim);
});

test('GraphQL loan query returns null for non-existent ID', function () {
    $this->postJson('/graphql', [
        'query' => '{ loan(id: "00000000-0000-0000-0000-000000000000") { id } }',
    ])->assertStatus(200)
      ->assertJsonPath('data.loan', null);
});

test('GraphiQL playground is accessible at /graphiql', function () {
    $this->get('/graphiql')
         ->assertStatus(200);
});

// ─────────────────────────────────────────────────────────────────────────────
// Response format consistency between REST and GraphQL
// ─────────────────────────────────────────────────────────────────────────────

test('GraphQL returns the same loan fields as REST GET /loans/{id}', function () {
    $loan = Loan::factory()->create();

    // REST
    $rest = $this->getJson("/api/v1/loans/{$loan->id}", ['X-IAE-KEY' => GQL_KEY])
                 ->assertStatus(200)
                 ->json('data.loan');

    // GraphQL
    $gql = $this->postJson('/graphql', [
        'query' => "{ loan(id: \"{$loan->id}\") { id applicant_name applicant_nim amount tenor_months purpose status notes } }",
    ])->assertStatus(200)
      ->json('data.loan');

    expect($rest['id'])->toBe($gql['id']);
    expect($rest['applicant_name'])->toBe($gql['applicant_name']);
    expect($rest['status'])->toBe($gql['status']);
});
