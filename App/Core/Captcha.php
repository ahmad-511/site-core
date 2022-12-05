<?php
declare (strict_types = 1);
namespace App\Core;

class Color{
	public $R;
	public $G;
	public $B;
	public $A;

	public function __construct($r, $g, $b, $a=null){
		$this->R=$r;
		$this->G=$g;
		$this->B=$b;
		$this->A=$a;
	}
}

class CaptchaResult{
	public $image = false;
	public string $code = '';

	public function __construct($image, $code)
	{
		$this->image = $image;
		$this->code = $code;
	}
}

class Captcha{
	public static Color $BackgroundColor;
	public static Color $LineColor;
	public static Color $PixelColor;
	public static Color $TextColor;
	public static string $Letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	public static int $LettersCount = 4;
	public static int $LetterSpacing = 30;
	public static string $Font = 'DroidSerifBold.ttf';
	public static int $FontSize = 20;

	public function __construct()
	{
		self::$BackgroundColor = self::$BackgroundColor??new Color(255, 255, 255);
		self::$LineColor = self::$LineColor??new Color(158, 158, 158);
		self::$PixelColor = self::$PixelColor??new Color(76, 175, 80);
		self::$TextColor = self::$TextColor??new Color(244, 67, 54);
		self::$Letters = self::$Letters??'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		self::$LettersCount = self::$LettersCount??4;
		self::$LetterSpacing = self::$LetterSpacing??30;
		self::$Font = self::$Font??'DroidSerifBold.ttf';
		self::$FontSize = self::$FontSize??20;
	}
	
	public static function Generate(){
		$width = self::$LettersCount * self::$LetterSpacing + 20;
		$height = 40;
		$image = \imagecreatetruecolor($width, $height);
		$backgroundColor = \imagecolorallocate($image, self::$BackgroundColor->R, self::$BackgroundColor->G, self::$BackgroundColor->B);
		$lineColor = \imagecolorallocate($image, self::$LineColor->R, self::$LineColor->G, self::$LineColor->B);
		$pixelColor = \imagecolorallocate($image, self::$PixelColor->R, self::$PixelColor->G, self::$PixelColor->B);
		$textColor = \imagecolorallocate($image, self::$TextColor->R, self::$TextColor->G, self::$TextColor->B);
		
		$code = '';

		\imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);
		
		for($i = 0; $i < $height / 4; $i++){
			\imageline($image, 0, rand() % $height, $width,rand() % $height, $lineColor);
		}
		
		for($i = 0; $i< $width * $height / 10; $i++){
			\imagesetpixel($image, rand() % $width, rand() % $height, $pixelColor);
		}
		
		for($i = 0; $i< self::$LettersCount; $i++) {
			$letter = self::$Letters[rand(0, strlen(self::$Letters) - 1)];
			\imagettftext($image, self::$FontSize, rand(-30, 30), 10 + ($i * self::$LetterSpacing), 30, $textColor, self::$Font, $letter);
			$code .= $letter;
		}

		return new CaptchaResult($image, $code);
	}
}
?>