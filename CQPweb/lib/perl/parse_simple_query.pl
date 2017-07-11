#paths are relative to PHP cwd, which is a fir within the CQPweb root
require "../lib/perl/CEQL.pm";
require "../lib/perl/CEQL/Parser.pm";

my $parser = new CEQL::Parser;

$query = $ARGV[0];
$case_sensitive = $ARGV[1];

$cqp_query = $parser->ceql_query($query, 1, "case_sensitive" => $case_sensitive);

print $cqp_query;
