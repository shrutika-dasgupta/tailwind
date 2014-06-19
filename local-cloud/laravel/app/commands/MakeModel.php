<?php

use Illuminate\Console\Command;

/**
 * Class MakeModel
 *
 * @use     php artisan model:make
 *
 * @author  Will
 */
class MakeModel extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'models:make';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Create a model and collection based on a table name';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $view_name = $this->option('type');
        if (empty($view_name)) {
            $view_name = $this->ask('eloquent or pdo?');
        }
        if (!in_array($view_name, ['eloquent', 'pdo'])) {
            $this->error("$view_name is not a valid model type to generate");
        }

        $table_name = $this->option('table');
        if (empty($table_name)) {
            $table_name = $this->ask('What is the table name?');
        }

        $model_name = $this->option('name');
        if (empty($model_name)) {
            $model_name = $this->ask('What should we call the model?');
        }

        $model_name      = ucfirst($model_name);
        $collection_name = ucfirst(str_plural($model_name));
        $this->info("The collection will be called $collection_name");

        $schema = new \Aura\SqlSchema\MysqlSchema(
            DB::getPdo(), new \Aura\SqlSchema\ColumnFactory()
        );

        $columns = $schema->fetchTableCols($table_name);

        $model      = View::make('commands.' . $view_name,
                                 array(
                                      'columns'    => $columns,
                                      'table_name' => $table_name,
                                      'model_name' => $model_name,
                                      'collection_name' => $collection_name
                                 )

        );
        $collection = View::make('commands.'.$view_name.'collection',
                                 array(
                                      'columns'         => $columns,
                                      'table_name'      => $table_name,
                                      'model_name'      => $model_name,
                                      'collection_name' => $collection_name
                                 )
        );

        file_put_contents(app_path() . '/models/' . $model_name . '.php', '<?php ' . $model);
        $this->info('Created /models/' . $model_name . '.php');

        file_put_contents(app_path() . '/collections/' . $collection_name . '.php', '<?php ' . $collection);
        $this->info('Created /collections/' . $collection_name . '.php');

    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return array(
            array('type', 'eloquent', 4, 'The type of model you want to make'),
            array('name', 'Foo', 4, 'The base name of the model and collection'),
            array('table', 'Foos', 4, 'The name of the table to build from'),
        );
    }
}
