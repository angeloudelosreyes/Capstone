    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateUsersFolderShareableTable extends Migration
    {
        public function up()
        {
            Schema::create('users_folder_shareable', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('users_id'); // Added users_id to indicate the creator
                $table->unsignedBigInteger('recipient_id')->nullable(); // Nullable recipient_id
                $table->string('title'); // Added title field for the shareable folder
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->timestamps();

                $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade'); // Nullable foreign key

            });
        }

        public function down()
        {
            Schema::dropIfExists('users_folder_shareable');
        }
    }
