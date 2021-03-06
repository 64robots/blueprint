<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\FormRequestGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see FormRequestGenerator
 */
class FormRequestGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var FormRequestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new FormRequestGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer($this->files));
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('get')
            ->with('stubs/form-request.stub')
            ->andReturn(file_get_contents('stubs/form-request.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_validate_statements()
    {
        $this->files->expects('get')
            ->with('stubs/form-request.stub')
            ->andReturn(file_get_contents('stubs/form-request.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('definitions/controllers-only.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_form_requests()
    {
        $this->files->expects('get')
            ->with('stubs/form-request.stub')
            ->andReturn(file_get_contents('stubs/form-request.stub'));

        $this->files->shouldReceive('exists')
            ->times(3)
            ->with('app/Http/Requests')
            ->andReturns(false, true, true);
        $this->files->expects('exists')
            ->with('app/Http/Requests/PostIndexRequest.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Http/Requests');
        $this->files->expects('put')
            ->with('app/Http/Requests/PostIndexRequest.php', $this->fixture('form-requests/post-index.php'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/PostStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/PostStoreRequest.php', $this->fixture('form-requests/post-store.php'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/OtherStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/OtherStoreRequest.php', $this->fixture('form-requests/other-store.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/validate-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Requests/PostIndexRequest.php', 'app/Http/Requests/PostStoreRequest.php', 'app/Http/Requests/OtherStoreRequest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_form_requests()
    {
        $this->files->expects('get')
            ->with('stubs/form-request.stub')
            ->andReturn(file_get_contents('stubs/form-request.stub'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/PostIndexRequest.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Http/Requests/PostStoreRequest.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Http/Requests/OtherStoreRequest.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('definitions/validate-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}