<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchableModelsTable extends Migration
{
    public function up()
    {
        Schema::create('searchable_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->nullableTimestamps();
        });
    }
}
