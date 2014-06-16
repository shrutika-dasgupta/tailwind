<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRestro extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::table('restro_list')->insert(array(
			'name'=>'Sarvana Bhavan',
			'location'=>'500 West 123rd Street'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Bhatti',
			'location'=>'567 West 23rd Street'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Navratna',
			'location'=>'600 East 50th Street'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Kailash Parbat',
			'location'=>'523 South 52nd Street'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'DhaBa',
			'location'=>'999 West 28th Street, Lexinton Avenue'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Sweet Bengal',
			'location'=>'524 West 59th street, Columbus Avenue'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Santosh VadaPaav',
			'location'=>'Vashi, Navi Mumbai'
			));

		DB::table('restro_list')->insert(array(
			'name'=>'Global Fusion',
			'location'=>'Saki naka, Andheri West Junction, Mumbai'
			));
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::table('restro_list')->where('name','=','Saravana Bhavan')->delete();
		DB::table('restro_list')->where('name','=','Bhatti')->delete();
		DB::table('restro_list')->where('name','=','Navratna')->delete();
		DB::table('restro_list')->where('name','=','Kailash Parbat')->delete();
		DB::table('restro_list')->where('name','=','DhaBa')->delete();
		DB::table('restro_list')->where('name','=','Sweet Bengal')->delete();
		DB::table('restro_list')->where('name','=','Santosh VadaPaav')->delete();
		DB::table('restro_list')->where('name','=','Global Fusion')->delete();
	}

}
