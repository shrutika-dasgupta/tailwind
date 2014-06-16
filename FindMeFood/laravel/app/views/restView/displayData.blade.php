@extends('layouts.restLayout')
@section('content')
	<h1>DisplayValues</h1>
	<style>
			table,td
			{
				border:1px solid;
			}
		</style>
	<p>
		<table>
			<tr><td><strong>Restaurants</strong></td><td><strong>Distance</strong></td><td><strong>Ratings</strong></td></tr>
			<?php 
				foreach ($result as $row) 
				{
					echo "<tr><td>".$row->name."</td><td>".$row->distance."</td><td>".$row->rating."</td></tr>";
				}
			?>
		</table>
	</p>
@stop