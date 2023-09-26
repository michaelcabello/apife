<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('ruc');
            $table->string('direccion');
            $table->string('logo_path')->nullable();

            //credenciales
            $table->string('sol_user');
            $table->string('sol_pass');
            $table->string('cert_path');

            //para guis electronicas//credenciales API
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();

            $table->boolean('production')->default(false);

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
