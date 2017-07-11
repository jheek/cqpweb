<?php




?>
#include <stdio.h>
#include "commandline.h"
#include "unicode.h"

/*
	This is loctransl, a version of lltransl.

	Note that unlike lltransl, it is NOT lossless.

	What it DOES do is standard indological transliteration.


	It follows the Library of Congress romanisation except where noted.

	NOTE: where LOC conflicts with ISO 15919 I have basically just picked one based on
	what can be rendered easily. Key rule: avoid diacritics wherever possible.

	NOTE: candrabindu and anusvara have single-character equivalents instead of varying.
	(traditional LOC transliterates anusvara as the homorganic nasal of the following stop,
	this is not followed here. traditional LOC transliterates candrabindu as n+cb before
	stops and m+cb elsewhere, this is not followed here)

	NOTE: Urdu has not yet been altered from lltransl

	NOTE: Sinhala has not yet been altered from lltransl

	NOTE: Non-devanagari bits have not yet been altered from lltransl

	NOTE: for now danda and double danda have just been left; stress marks not done 
	yet either

	NOTE: nukta on its own (093c) does not process

	NOTE: vocalic r and retroflex r go to the same (r with subscript dot)
	because Unicode doesn't have single characters for the subscript little circle

	NOTE: x is used for "kh+nukta". Becasue there is no underlined h in Unicode.
	Similarly for "gh+nukta", I'm using g with superscript dot.
*/





/* glyphtype stuff */
#define OTHER 0
#define VIRAMA 1
#define CONSONANT 2
#define IND_VOWEL 3
#define VOWEL 4
int glyphtype(unichar ch);



main(int argc, char *argv[])
{
	FILE *source;
	FILE *dest;

	unichar current, next;
	unichar astr[] = { 0x0061, 0 };
	unichar target[30];

	if (argc != 3)
	{
		cl_error_arguments(3, argc);
		return 1;
	}

	/* open the source file and check for (then discard) directionality character */
	if (!(source = fopen(argv[1], "rb")))
	{
		cl_error_file_open(argv[1]);
		fcloseall();
		return 1;
	}
	if (!( ucheckdir(source) ))
	{
		fputs("Specified source file not recognised as Unicode!\n", stderr);
		fcloseall();
		return 1;
	}

	/* check that the target filename won't be overwritten */
	if (cl_test_file_write(argv[2]))
	{
		fcloseall();
		return 0;
	}
	
	/* open file to write, insert directionality character */
	if( !(dest = fopen(argv[2], "wb")) )
	{
		cl_error_file_open(argv[2]);
		fcloseall();
		return 1;
	}
	if ( fputuc( RIGHTWAY , dest) == UERR )
	{
		cl_error_file_write(argv[2]);
		fcloseall();
		return 1;
	}

	current = 0;
	next = 0;

	while (1)
	{
		/* load a character to next. */
		current = next;
		next = fgetuc(source);

		

		/* cycle past start of file */
		if (!current)
			continue;
		/* check for end of file */
		if (current == UERR)
			break;


		/* adjustments for dev-like scripts */
		/* this doesn't cover script specific additions; those still need to be specified */
		/* and excepted from this subtraction */
		/* Bengali */
		if ( current > 0x0980 && current < 0x09ff )
			current -= 0x80;
		/* Gurmukhi */
		if ( current > 0x0a00 && current < 0x0a7f )
			current -= 0x100;
		/* Gujarati */
		if ( current > 0x0a80 && current < 0x0aff )
			current -= 0x180;
		/* Oriya */
		if ( current > 0x0b00 && current < 0x0b7f )
			current -= 0x200;
		/* Tamil */
		if ( current > 0x0b80 && current < 0x0bff )
			current -= 0x280;
		/* Telugu */
		if ( current > 0x0c00 && current < 0x0c7f )
			current -= 0x300;
		/* Kannada */
		if ( current > 0x0c80 && current < 0x0cff )
			current -= 0x380;
		/* Malayalam */
		if ( current > 0x0d00 && current < 0x0d7f )
			current -= 0x400;

		/* etc. */

		// and Sinhala will need to be completely different! ... see below



		/* look at the character */
		switch (current)
		{
		/* URDU */
		case 0x060c:
		case 0x060d:	target[0] = 0x002c;		target[1] = 0;	break;
		case 0x060e:	target[0] = 0x002e;		target[1] = 0;	break;
		case 0x0610:
		case 0x0611:
		case 0x0612:
		case 0x0613:
		case 0x0614:
		case 0x0615:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x061b:	target[0] = 0x003b;		target[1] = 0;	break;
		case 0x061f:	target[0] = 0x003f;		target[1] = 0;	break;
		case 0x0621:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x0622:	target[0] = 0x0061;		target[1] = 0x0061;		target[2] = 0;	break;
		case 0x0623:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x0624:	target[0] = 0x0027;		target[1] = 0x0076;		target[2] = 0;	break;
		case 0x0625:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x0626:	target[0] = 0x0027;		target[1] = 0x0079;		target[2] = 0;	break;
		case 0x0627:	target[0] = 0x0061;		target[1] = 0x0061;		target[2] = 0;	break;
		case 0x0628:	target[0] = 0x0062;		target[1] = 0;	break;
		case 0x0629:
		case 0x062a:	target[0] = 0x0074;		target[1] = 0;	break;
		case 0x062b:	target[0] = 0x0073;		target[1] = 0;	break;
		case 0x062c:	target[0] = 0x006a;		target[1] = 0;	break;
		case 0x062d:	target[0] = 0x0068;		target[1] = 0;	break;
		case 0x062e:	target[0] = 0x0078;		target[1] = 0;	break;
		case 0x062f:	target[0] = 0x0044;		target[1] = 0;	break;
		case 0x0630:	target[0] = 0x007a;		target[1] = 0;	break;
		case 0x0631:	target[0] = 0x0072;		target[1] = 0;	break;
		case 0x0632:	target[0] = 0x007a;		target[1] = 0;	break;
		case 0x0633:	target[0] = 0x0073;		target[1] = 0;	break;
		case 0x0634:	target[0] = 0x0073;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0635:	target[0] = 0x0073;		target[1] = 0;	break;
		case 0x0636:	target[0] = 0x007a;		target[1] = 0;	break;
		case 0x0637:	target[0] = 0x0074;		target[1] = 0;	break;
		case 0x0638:	target[0] = 0x007a;		target[1] = 0;	break;
		case 0x0639:	target[0] = 0x0040;		target[1] = 0;	break;
		case 0x063a:	target[0] = 0x0047;		target[1] = 0;	break;
		case 0x0640:	continue;
		case 0x0641:	target[0] = 0x0066;		target[1] = 0;	break;
		case 0x0642:	target[0] = 0x0071;		target[1] = 0;	break;
		case 0x0643:	target[0] = 0x006b;		target[1] = 0;	break;
		case 0x0644:	target[0] = 0x006c;		target[1] = 0;	break;
		case 0x0645:	target[0] = 0x006d;		target[1] = 0;	break;
		case 0x0646:	target[0] = 0x006e;		target[1] = 0;	break;
		case 0x0647:	target[0] = 0x0068;		target[1] = 0;	break;
		case 0x0648:	target[0] = 0x0076;		target[1] = 0;	break;
		case 0x0649:	target[0] = 0x0079;		target[1] = 0;	break;
		case 0x064a:	target[0] = 0x0079;		target[1] = 0;	break;
		//case 0x064b:	target[0] = 0x00;		target[1] = 0;	break;
		//case 0x064c:	target[0] = 0x00;		target[1] = 0;	break;
		//case 0x064d:	target[0] = 0x00;		target[1] = 0;	break;
		case 0x064e:	target[0] = 0x0061;		target[1] = 0;	break;
		case 0x064f:	target[0] = 0x0075;		target[1] = 0;	break;
		case 0x0650:	target[0] = 0x0069;		target[1] = 0;	break;
		//case 0x0651:	target[0] = previous;	target[1] = 0;	break;
		//case 0x0652:	target[0] = 0x00;		target[1] = 0;	break;
		case 0x0653:	continue;
		case 0x0654:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x0655:	target[0] = 0x0027;		target[1] = 0;	break;
		case 0x0660:	target[0] = 0x0030;		target[1] = 0;	break;
		case 0x0661:	target[0] = 0x0031;		target[1] = 0;	break;
		case 0x0662:	target[0] = 0x0032;		target[1] = 0;	break;
		case 0x0663:	target[0] = 0x0033;		target[1] = 0;	break;
		case 0x0664:	target[0] = 0x0034;		target[1] = 0;	break;
		case 0x0665:	target[0] = 0x0035;		target[1] = 0;	break;
		case 0x0666:	target[0] = 0x0036;		target[1] = 0;	break;
		case 0x0667:	target[0] = 0x0037;		target[1] = 0;	break;
		case 0x0668:	target[0] = 0x0038;		target[1] = 0;	break;
		case 0x0669:	target[0] = 0x0039;		target[1] = 0;	break;
		case 0x066a:	target[0] = 0x0025;		target[1] = 0;	break;
		case 0x066b:	target[0] = 0x002c;		target[1] = 0;	break;
		case 0x066c:	target[0] = 0x002c;		target[1] = 0;	break;
		case 0x066d:	target[0] = 0x002a;		target[1] = 0;	break;
		case 0x0670:	target[0] = 0x0061;		target[1] = 0;	break;
		case 0x0679:	target[0] = 0x0054;		target[1] = 0;	break;
		case 0x067e:	target[0] = 0x0070;		target[1] = 0;	break;
		case 0x0686:	target[0] = 0x0063;		target[1] = 0;	break;
		case 0x0688:	target[0] = 0x0044;		target[1] = 0;	break;
		case 0x0691:	target[0] = 0x0052;		target[1] = 0;	break;
		case 0x0698:	target[0] = 0x007a;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x06a9:	target[0] = 0x006b;		target[1] = 0;	break;
		case 0x06af:	target[0] = 0x0067;		target[1] = 0;	break;
		case 0x06ba:	target[0] = 0x007e;		target[1] = 0;	break;
		case 0x06be:	target[0] = 0x0068;		target[1] = 0;	break;
		case 0x06c0:	target[0] = 0x0068;		target[1] = 0x0065;		target[2] = 0;	break;
		case 0x06c1:	target[0] = 0x0068;		target[1] = 0;	break;
		case 0x06c2:	target[0] = 0x0068;		target[1] = 0x0065;		target[2] = 0;	break;
		case 0x06c3:	target[0] = 0x0074;		target[1] = 0;	break;
		case 0x06cc:	target[0] = 0x0079;		target[1] = 0;	break;
		case 0x06d2:	target[0] = 0x0065;		target[1] = 0;	break;
		case 0x06d3:	target[0] = 0x0027;		target[1] = 0x0065;		target[2] = 0;	break;
		case 0x06d4:	target[0] = 0x002e;		target[1] = 0;	break;
		case 0x06f0:	target[0] = 0x0030;		target[1] = 0;	break;
		case 0x06f1:	target[0] = 0x0031;		target[1] = 0;	break;
		case 0x06f2:	target[0] = 0x0032;		target[1] = 0;	break;
		case 0x06f3:	target[0] = 0x0033;		target[1] = 0;	break;
		case 0x06f4:	target[0] = 0x0034;		target[1] = 0;	break;
		case 0x06f5:	target[0] = 0x0035;		target[1] = 0;	break;
		case 0x06f6:	target[0] = 0x0036;		target[1] = 0;	break;
		case 0x06f7:	target[0] = 0x0037;		target[1] = 0;	break;
		case 0x06f8:	target[0] = 0x0038;		target[1] = 0;	break;
		case 0x06f9:	target[0] = 0x0039;		target[1] = 0;	break;

		/* DEVANAGARI */
		/*
		case 0x0901:	target[0] = 'n';		target[1] = 0x0310;		target[2] = 0;	break;
		*/
		case 0x0901:	target[0] = 0x0148;		target[1] = 0;	break;
		case 0x0902:	target[0] = 0x1e41;		target[1] = 0;	break;
		case 0x0903:	target[0] = 0x1e25;		target[1] = 0;	break;
		case 0x0904:	target[0] = 0x0103;		target[1] = 0;	break;
		case 0x0905:	target[0] = 'a';		target[1] = 0;	break;
		case 0x0906:	target[0] = 0x0101;		target[1] = 0;	break;
		case 0x0907:	target[0] = 'i';		target[1] = 0;	break;
		case 0x0908:	target[0] = 0x012b;		target[1] = 0;	break;
		case 0x0909:	target[0] = 'u';		target[1] = 0;	break;
		case 0x090a:	target[0] = 0x016b;		target[1] = 0;	break;
		case 0x090b:	target[0] = 0x1e5b;		target[1] = 0;	break;
		case 0x090c:	target[0] = 0x1e37;		target[1] = 0;	break;
		case 0x090d:	target[0] = 0x00ea;		target[1] = 0;	break;
		case 0x090e:	target[0] = 0x0115;		target[1] = 0;	break;
		case 0x090f:	target[0] = 'e';		target[1] = 0;	break;
		case 0x0910:	target[0] = 'a';		target[1] = 'i';		target[2] = 0;	break;
		case 0x0911:	target[0] = 0x00f4;		target[1] = 0;	break;
		case 0x0912:	target[0] = 0x014f;		target[1] = 0;	break;
		case 0x0913:	target[0] = 'o';		target[1] = 0;	break;
		case 0x0914:	target[0] = 'a';		target[1] = 'u';		target[2] = 0;	break;
		case 0x0915:	target[0] = 0x006b;		target[1] = 0;	break;
		case 0x0916:	target[0] = 0x006b;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0917:	target[0] = 0x0067;		target[1] = 0;	break;
		case 0x0918:	target[0] = 0x0067;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0919:	target[0] = 0x1e45;		target[1] = 0;	break;
		case 0x091a:	target[0] = 0x0063;		target[1] = 0;	break;
		case 0x091b:	target[0] = 0x0063;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x091c:	target[0] = 0x006a;		target[1] = 0;	break;
		case 0x091d:	target[0] = 0x006a;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x091e:	target[0] = 0x00f1;		target[1] = 0;	break;
		case 0x091f:	target[0] = 0x1e6d;		target[1] = 0;	break;
		case 0x0920:	target[0] = 0x1e6d;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0921:	target[0] = 0x1e0d;		target[1] = 0;	break;
		case 0x0922:	target[0] = 0x1e0d;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0923:	target[0] = 0x1e47;		target[1] = 0;	break;
		case 0x0924:	target[0] = 0x0074;		target[1] = 0;	break;
		case 0x0925:	target[0] = 0x0074;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0926:	target[0] = 0x0064;		target[1] = 0;	break;
		case 0x0927:	target[0] = 0x0064;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x0928:	target[0] = 0x006e;		target[1] = 0;	break;
		case 0x0929:	target[0] = 0x1e49;		target[1] = 0;	break;
		case 0x092a:	target[0] = 0x0070;		target[1] = 0;	break;
		case 0x092b:	target[0] = 0x0070;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x092c:	target[0] = 0x0062;		target[1] = 0;	break;
		case 0x092d:	target[0] = 0x0062;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x092e:	target[0] = 0x006d;		target[1] = 0;	break;
		case 0x092f:	target[0] = 0x0079;		target[1] = 0;	break;
		case 0x0930:	target[0] = 0x0072;		target[1] = 0;	break;
		case 0x0931:	target[0] = 0x1e5f;		target[1] = 0;	break;
		case 0x0932:	target[0] = 0x006c;		target[1] = 0;	break;
		case 0x0933:	target[0] = 0x1e37;		target[1] = 0;	break;
		case 0x0934:	target[0] = 0x1e3b;		target[1] = 0;	break;
		case 0x0935:	target[0] = 0x0076;		target[1] = 0;	break;
		case 0x0936:	target[0] = 0x015b;		target[1] = 0;	break;
		case 0x0937:	target[0] = 0x1e63;		target[1] = 0;	break;
		case 0x0938:	target[0] = 0x0073;		target[1] = 0;	break;
		case 0x0939:	target[0] = 0x0068;		target[1] = 0;	break;
		case 0x093c:	target[0] = 0x0023;		target[1] = 0;	break;
		case 0x093e:	target[0] = 0x0101;		target[1] = 0;	break;
		case 0x093d:	target[0] = 0x2019;		target[1] = 0;	break;
		case 0x093f:	target[0] = 0x0069;		target[1] = 0;	break;
		case 0x0940:	target[0] = 0x012b;		target[1] = 0;	break;
		case 0x0941:	target[0] = 0x0075;		target[1] = 0;	break;
		case 0x0942:	target[0] = 0x016b;		target[1] = 0;	break;
		case 0x0943:	target[0] = 0x1e5b;		target[1] = 0;	break;
		case 0x0944:	target[0] = 0x1e5d;		target[1] = 0;	break;
		case 0x0945:	target[0] = 0x00ea;		target[1] = 0;	break;
		case 0x0946:	target[0] = 0x0115;		target[1] = 0;	break;
		case 0x0947:	target[0] = 0x0065;		target[1] = 0;	break;
		case 0x0948:	target[0] = 0x0061;		target[1] = 0x0069;		target[2] = 0;	break;
		case 0x0949:	target[0] = 0x00f4;		target[1] = 0;	break;
		case 0x094a:	target[0] = 0x014f;		target[1] = 0;	break;
		case 0x094b:	target[0] = 0x006f;		target[1] = 0;	break;
		case 0x094c:	target[0] = 0x0061;		target[1] = 0x0075;		target[2] = 0;	break;
		case 0x094d:	continue;
//		case 0x0950:	target[0] = 0x004f;		target[1] = 0x004d;		target[2] = 0;	break;
		case 0x0951:	target[0] = '*';		target[1] = 0;	break;
		case 0x0952:	target[0] = '_';		target[1] = 0;	break;
		case 0x0953:	target[0] = '`';		target[1] = 0;	break;
		case 0x0954:	target[0] = 0x00b4;		target[1] = 0;	break;

		// this is for the Bengali AU length mark which of course shouldn't actually occur
// note of course, going to 3a is not lossless yet.
		case 0x0957:	target[0] = 0x01e1;		target[1] = 0;	break;
		
		case 0x0958:	target[0] = 0x0071;		target[1] = 0;	break;
		case 0x0959:	target[0] = 'x';		target[1] = 0;	break;
		case 0x095a:	target[0] = 0x0121;		target[1] = 0;	break;
		case 0x095b:	target[0] = 0x007a;		target[1] = 0;	break;
		case 0x095c:	target[0] = 0x1e5b;		target[1] = 0;	break;
		case 0x095d:	target[0] = 0x1e5b;		target[1] = 0x0068;		target[2] = 0;	break;
		case 0x095e:	target[0] = 0x0066;		target[1] = 0;	break;
		case 0x095f:	target[0] = 0x1e8f;		target[1] = 0;	break;
		case 0x0960:	target[0] = 0x1e5d;		target[1] = 0;	break;
		case 0x0961:	target[0] = 0x1e39;		target[1] = 0;	break;
		case 0x0962:	target[0] = 0x1e37;		target[1] = 0;	break;
		case 0x0963:	target[0] = 0x1e39;		target[1] = 0;	break;

		// actually, in lossless, leave these two as-is
//		case 0x0964:	target[0] = 0x002e;		target[1] = 0;	break;
//		case 0x0965:	target[0] = 0x002e;		target[1] = 0;	break;
		case 0x0966:	target[0] = 0x0030;		target[1] = 0;	break;
		case 0x0967:	target[0] = 0x0031;		target[1] = 0;	break;
		case 0x0968:	target[0] = 0x0032;		target[1] = 0;	break;
		case 0x0969:	target[0] = 0x0033;		target[1] = 0;	break;
		case 0x096a:	target[0] = 0x0034;		target[1] = 0;	break;
		case 0x096b:	target[0] = 0x0035;		target[1] = 0;	break;
		case 0x096c:	target[0] = 0x0036;		target[1] = 0;	break;
		case 0x096d:	target[0] = 0x0037;		target[1] = 0;	break;
		case 0x096e:	target[0] = 0x0038;		target[1] = 0;	break;
		case 0x096f:	target[0] = 0x0039;		target[1] = 0;	break;

		// left as-is for same reason as danda and double danda.
		case 0x0970:	target[0] = 0x002e;		target[1] = 0;	break;
		
		/* lang-speciifc additions? */

		/* Sinhala -- all is specific & v. rough based on Unicode standard descriptions */
		case 0x0d82:	target[0] = '~';	target[1] = 0;		break;
		case 0x0d83:	target[0] = 'h';	target[1] = 0;		break;
		case 0x0d85:	target[0] = 'a';	target[1] = 0;		break;
		case 0x0d86:	target[0] = 'a';	target[1] = 'a';	target[2] = 0;		break;
		case 0x0d87:	target[0] = 'a';	target[1] = 'e';	target[2] = 0;		break;
		case 0x0d88:	target[0] = 'a';	target[1] = 'a';	target[2] = 'e';	target[3] = 0;	break;
		case 0x0d89:	target[0] = 'i';	target[1] = 0;		break;
		case 0x0d8a:	target[0] = 'i';	target[1] = 'i';	target[2] = 0;		break;
		case 0x0d8b:	target[0] = 'u';	target[1] = 0;		break;
		case 0x0d8c:	target[0] = 'u';	target[1] = 'u';	target[2] = 0;		break;
		case 0x0d8d:	target[0] = 'r';	target[1] = 0;		break;
		case 0x0d8e:	target[0] = 'r';	target[1] = 'r';	target[2] = 0;		break;
		case 0x0d8f:	target[0] = 'l';	target[1] = 0;		break;
		case 0x0d90:	target[0] = 'l';	target[1] = 'l';	target[2] = 0;		break;
		case 0x0d91:	target[0] = 'e';	target[1] = 0;		break;
		case 0x0d92:	target[0] = 'e';	target[1] = 'e';	target[2] = 0;		break;
		case 0x0d93:	target[0] = 'a';	target[1] = 'i';	target[2] = 0;		break;
		case 0x0d94:	target[0] = 'o';	target[1] = 0;		break;
		case 0x0d95:	target[0] = 'o';	target[1] = 'o';	target[2] = 0;		break;
		case 0x0d96:	target[0] = 'a';	target[1] = 'u';	target[2] = 0;		break;

		case 0x0d9a:	target[0] = 'k';	target[1] = 0;		break;
		case 0x0d9b:	target[0] = 'k';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0d9c:	target[0] = 'g';	target[1] = 0;		break;
		case 0x0d9d:	target[0] = 'g';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0d9e:	target[0] = 'n';	target[1] = 'g';	target[2] = 0;		break;
		case 0x0d9f:	target[0] = 'n';	target[1] = 'n';	target[2] = 'g';	target[3] = 0;	break;
		case 0x0da0:	target[0] = 'c';	target[1] = 0;		break;
		case 0x0da1:	target[0] = 'c';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0da2:	target[0] = 'j';	target[1] = 0;		break;
		case 0x0da3:	target[0] = 'j';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0da4:	target[0] = 'n';	target[1] = 'y';	target[2] = 0;		break;
		case 0x0da5:	target[0] = 'j';	target[1] = 'n';	target[2] = 'y';	target[3] = 0;	break;
		case 0x0da6:	target[0] = 'n';	target[1] = 'y';	target[2] = 'j';	target[3] = 0;	break;
		case 0x0da7:	target[0] = 'T';	target[1] = 0;		break;
		case 0x0da8:	target[0] = 'T';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0da9:	target[0] = 'D';	target[1] = 0;		break;
		case 0x0daa:	target[0] = 'D';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0dab:	target[0] = 'N';	target[1] = 0;		break;
		case 0x0dac:	target[0] = 'N';	target[1] = 'D';	target[2] = 0;		break;
		case 0x0dad:	target[0] = 't';	target[1] = 0;		break;
		case 0x0dae:	target[0] = 't';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0daf:	target[0] = 'd';	target[1] = 0;		break;
		case 0x0db0:	target[0] = 'd';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0db1:	target[0] = 'n';	target[1] = 0;		break;

		case 0x0db3:	target[0] = 'n';	target[1] = 'd';	target[2] = 0;		break;
		case 0x0db4:	target[0] = 'p';	target[1] = 0;		break;
		case 0x0db5:	target[0] = 'p';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0db6:	target[0] = 'b';	target[1] = 0;		break;
		case 0x0db7:	target[0] = 'b';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0db8:	target[0] = 'm';	target[1] = 0;		break;
		case 0x0db9:	target[0] = 'm';	target[1] = 'b';	target[2] = 0;		break;
		case 0x0dba:	target[0] = 'y';	target[1] = 0;		break;
		case 0x0dbb:	target[0] = 'r';	target[1] = 0;		break;

		case 0x0dbd:	target[0] = 'l';	target[1] = 0;		break;

		case 0x0dc0:	target[0] = 'v';	target[1] = 0;		break;
		case 0x0dc1:	target[0] = 's';	target[1] = 'h';	target[2] = 0;		break;
		case 0x0dc2:	target[0] = 'S';	target[1] = 0;		break;
		case 0x0dc3:	target[0] = 's';	target[1] = 0;		break;
		case 0x0dc4:	target[0] = 'h';	target[1] = 0;		break;
		case 0x0dc5:	target[0] = 'L';	target[1] = 0;		break;
		case 0x0dc6:	target[0] = 'f';	target[1] = 0;		break;

		case 0x0dca:	continue;
	
		case 0x0dcf:	target[0] = 'a';	target[1] = 'a';	target[2] = 0;		break;
		case 0x0dd0:	target[0] = 'a';	target[1] = 'e';	target[2] = 0;		break;
		case 0x0dd1:	target[0] = 'a';	target[1] = 'a';	target[2] = 'e';	target[3] = 0;	break;
		case 0x0dd2:	target[0] = 'i';	target[1] = 0;		break;
		case 0x0dd3:	target[0] = 'i';	target[1] = 'i';	target[2] = 0;		break;
		case 0x0dd4:	target[0] = 'u';	target[1] = 0;		break;

		case 0x0dd6:	target[0] = 'u';	target[1] = 'u';	target[2] = 0;		break;

		case 0x0dd8:	target[0] = 'r';	target[1] = 0;		break;
		case 0x0dd9:	target[0] = 'e';	target[1] = 0;		break;
		case 0x0dda:	target[0] = 'e';	target[1] = 'e';	target[2] = 0;		break;
		case 0x0ddb:	target[0] = 'a';	target[1] = 'i';	target[2] = 0;		break;
		case 0x0ddc:	target[0] = 'o';	target[1] = 0;		break;
		case 0x0ddd:	target[0] = 'o';	target[1] = 'o';	target[2] = 0;		break;
		case 0x0dde:	target[0] = 'a';	target[1] = 'u';	target[2] = 0;		break;
		case 0x0ddf:	target[0] = 'l';	target[1] = 0;		break;

		case 0x0df2:	target[0] = 'r';	target[1] = 'r';	target[2] = 0;		break;
		case 0x0df3:	target[0] = 'l';	target[1] = 'l';	target[2] = 0;		break;
		case 0x0df4:	target[0] = '.';	target[1] = 0;		break;



		default:		target[0] = current;	target[1] = 0;	break;
		}

		/* adjust indic virama */
		if (current > 0x0900 && current < 0x0e00 &&
			(glyphtype(current) == CONSONANT || current == 0x093c ) )
		{
			if (glyphtype(next) == VIRAMA ||
				( glyphtype(next) == VOWEL && next != 0x0902 && next != 0x0901) ||
				next == 0x093c)
				;
			else
				/* add an "a" to the target string */
				ustrcat(target, astr);
		}

		/* write the target */
		if (fputus(target, dest) == EOF)
		{
			cl_error_file_write(argv[2]);
			fcloseall();
			return 1;
		}
	}



	/* close read and write files */
	if (fclose(source) < 0)
	{
		cl_error_file_close(argv[1]);
		fcloseall();
		return 1;
	}
	if (fclose(dest) < 0)
	{
		cl_error_file_close(argv[2]);
		fcloseall();
		return 1;
	}

	return 0;
}










/* for ease of computation, this is a copy of Unicodify's glyphtype. */
/* Remember to update this as that algorithm changes! */

int glyphtype(unichar ch)
{
	/* Hindi / Devanagari */

	if (ch == 0x094d)
		return VIRAMA;
	if ( (ch > 0x0914 && ch < 0x093a) || (ch > 0x0957 && ch < 0x0960 ) )
		return CONSONANT;
	if ( (ch > 0x0900 && ch < 0x0904) || (ch > 0x093d && ch < 0x094d) || (ch > 0x0950 && ch < 0x0955) || (ch > 0x0961 && ch < 0x0964) )
		return VOWEL;
	if ( (ch > 0x0904 && ch < 0x0915) || (ch > 0x095f && ch < 0x0962 ) )
		return IND_VOWEL;

	/* Bengali */

	if (ch == 0x09cd)
		return VIRAMA;
	if ( (ch > 0x0994 && ch < 0x09ba) || (ch > 0x09db && ch < 0x0960) || (ch > 0x09ef && ch < 0x09f2) )
		return CONSONANT;
	if ( (ch > 0x0980 && ch < 0x0984) || (ch > 0x09bd && ch < 0x09cd) || (ch == 0x09d7) || (ch > 0x09e1 && ch < 0x09e4) )
		return VOWEL;
	if ( (ch > 0x0984 && ch < 0x0995) || (ch > 0x09df && ch < 0x09e2 ) )
		return IND_VOWEL;

	/* Punjabi */

	if (ch == 0x0a4d)
		return VIRAMA;
	if ( (ch > 0x0a14 && ch < 0x0a3a) || (ch > 0x0a58 && ch < 0x0a5f) )
		return CONSONANT;
	if ( (ch > 0x0a00 && ch < 0x0a4d) || (ch == 0x0a3c) || (ch > 0x0a6f && ch < 0x0a72) )
		return VOWEL;
	if ( (ch > 0x0a04 && ch < 0x0a15) || (ch > 0x0a71 && ch < 0x0a75) )
		return IND_VOWEL;

	/*  Gujarati */

	if (ch == 0x0acd)
		return VIRAMA;
	if (ch > 0x0a94 && ch < 0x0aba)
		return CONSONANT;
	if ( (ch > 0x0a80 && ch < 0x0a84) || (ch == 0x0abc) || (ch > 0x0abd && ch < 0x0acd) )
		return VOWEL;
	if (ch == 0x0ae0 || (ch > 0x0a84 && ch < 0x0a95) )
		return IND_VOWEL;

	/* Tamil */

	if (ch == 0x0bcd)
		return VIRAMA;
	if (ch > 0x0b94 && ch < 0x0bba)
		return CONSONANT;
	if ( (ch > 0x0b81 && ch < 0x0b84) || (ch > 0x0bbd && ch < 0x0bcd) )
		return VOWEL;
	if (ch > 0x0b84 && ch < 0x0b95)
		return IND_VOWEL;

	/* Sinhala */

	if (ch == 0x0dca)
		return VIRAMA;
	if (ch > 0x0d99 && ch < 0x0dc7)
		return CONSONANT;
	if ( (ch > 0x0d81 && ch < 0x0d84) || (ch > 0x0dce && ch < 0x0df4) )
		return VOWEL;
	if (ch > 0x0d84 && ch < 0x0d97)
		return IND_VOWEL;


	return OTHER;
}