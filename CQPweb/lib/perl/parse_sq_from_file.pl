
use CEQL;
require CEQL::Parser;
my $parser = new CEQL::Parser;



open QUERYFILE, $ARGV[0] or die $!;
@lines = <QUERYFILE>;
close(QUERYFILE);
$query = join(' ' , @lines);

$case_sensitive = $ARGV[1];



$cqp_query = $parser->ceql_query($query, 1, "case_sensitive" => $case_sensitive);

print $cqp_query;
