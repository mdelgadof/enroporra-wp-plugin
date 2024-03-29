<?php
function mb_ucfirst($string): string {
	$firstChar = mb_substr($string, 0, 1);
	$then = mb_substr($string, 1 );
	return mb_strtoupper($firstChar) . $then;
}
function normalizeSpanishName($name) {
	$prepositions = array("de","las","los","del","la","y","i");
	$proper =       array("Íñigo","Cámara","Nicolás","Tomás","Tenés","Germán","D'Augnat","Ávila","Víctor","González","Fernández","Gómez","Martínez","Martín","García","Vázquez","Blázquez","López","Pérez","Sánchez","Alarcón","Peribáñez","Ríos","Cortés","Jiménez","Giménez","Macías","Rocío","Óscar","César","Mónica","Joaquín","Rodríguez","Román","María","Ramírez","Marín","Marina","Durán","Gálvez","Belén","Díaz","Núñez","Verónica","Menéndez","Martí","León","José","Márquez","Bermúdez","Hernández","Lucía","Gutiérrez","Méndez","Darío","Iván","Rubén","Álvarez","Andrés","Raúl","Beltrán","Héctor","Guillén","Ginés","Jesús","Mínguez","Dávila","Fátima","Peláez","Adrián","Ramón","Álvaro","Álvaro","Ibáñez","Roldán","Suárez","Ángel","Gárate","Báez","Ocón","Marañón","Solís","Inés");
	$incorrect =    array("Iñigo","Camara","Nicolas","Tomas","Tenes","German","D'augnat","Avila","Victor","Gonzalez","Fernandez","Gomez","Martinez","Martin","Garcia","Vazquez","Blazquez","Lopez","Perez","Sanchez","Alarcon","Peribañez","Rios","Cortes","Jimenez","Gimenez","Macias","Rocio","Oscar","Cesar","Monica","Joaquin","Rodriguez","Roman","Maria","Ramirez","Marin","Marína","Duran","Galvez","Belen","Diaz","Nuñez","Veronica","Menendez","Marti","Leon","Jose","Marquez","Bermudez","Hernandez","Lucia","Gutierrez","Mendez","Dario","Ivan","Ruben","Alvarez","Andres","Raul","Beltran","Hector","Guillen","Gines","Jesus","Minguez","Davila","Fatima","Pelaez","Adrian","Ramon","Alvaro","álvaro","Ibañez","Roldan","Suarez","Angel","Garate","Baez","Ocon","Marañon","Solis","Ines");
	$words = explode(" ",trim($name));
	$name_after=array();
	foreach ($words as $word) {
		$word = mb_strtolower($word);
		if (!in_array($word,$prepositions)) $word=mb_ucfirst($word);
		if (($key = array_search($word,$incorrect))!==false)
			$word = $proper[$key];
		$name_after[]=$word;
	}
	return implode(" ",$name_after);
}