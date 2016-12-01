<?php
 include("phpequations.class.php");
 $equations = new phpequations();
?>
<pre>

<h1>Examples for using phpEquations</h1>


<h2>Roots of a linear equation</h2>

	5 - x + 2*4 = 88

<?
 print_r($equations->solve("

 	5 - x + 2*4 = 88

	"));
?>

<h2>System of linear equations</h2>

	a+b+2*c=5
	a-b = u
	u-2*a=4
	2*b=a

<?
 print_r($equations->solve("

	a+b+2*c=5
	a-b = u
	u-2*a=4
	2*b=a

	"));
?>

<h2>Roots of a polynomial equation</h2>

	3*pow(x,5) + 9*pow(x,3)+ x  = 8

<?
 print_r($equations->solve("

	3*pow(x,5) + 9*pow(x,3)+ x  = 8

	"));
?>

<h2>System of polynomial equations</h2>

	pow(x,3)/7 +6*pow(y,2) + 5*pow(u,4) = 70
	pow(x,4)/4 + 3*pow(y,3) + 2*pow(u,3) = 40
	3*pow(x,3) +2*pow(y,2)   = 0

<?
 print_r($equations->solve("

	pow(x,3)/7 +6*pow(y,2) + 5*pow(u,4) = 70
	pow(x,4)/4 + 3*pow(y,3) + 2*pow(u,3) = 40
	3*pow(x,3) +2*pow(y,2)   = 0

	"));
?>

<h2>Roots of a trigonometric equation</h2>

	sin(A) + cos(A) = 0.5

<?
 print_r($equations->solve("

	sin(A) + cos(A) = 0.5

	"));
?>

<h2>System of trigonometric equations</h2>

	sin(A) + cos(B) = 0.5
	pow(sin(A),2) - pow(cos(B),2) = 0.1

<?
 print_r($equations->solve("

	sin(A) + cos(B) = 0.5
	pow(sin(A),2) - pow(cos(B),2) = 0.1

	"));
?>

<h2>Volume of sphere</h2>
What will be the radius of sphere having volume of 10 m^3?

	V = (4/3)*PI#*(pow(R,3))
	V = 10

<?
 print_r($equations->solve("

	V = (4/3)*PI#*(pow(R,3))
	V = 10

	"));
?>

<h2>Height of building</h2>
One can see the top of a building at 35 deg from horizontal. Building is 20 m away. What is the height of building?

	ANGLE_DEG = 35
	DISTANCE = 20
	tan(ANGLE_DEG*PI#/180)=HEIGHT/DISTANCE

<?
 print_r($equations->solve("

	ANGLE_DEG = 35
	DISTANCE = 20
	tan(ANGLE_DEG*PI#/180)=HEIGHT/DISTANCE

	"));
?>