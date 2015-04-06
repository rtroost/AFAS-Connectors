# AFAS-Connectors

## Gegevens selecteren
```
# Instantiate a new AfasGetConnector
$connector = new AfasGetConnector();

# Authentication
$connector->setEnvironment( 'omgeving' );
$connector->setCredentials( 'gebruikersnaam', 'wachtwoord' );
$connector->setConnector( 'connectorid' );

# Make the call
$result = $connector->execute();
```
## Gegevens toevoegen
```
# Instantiate a new AfasUpdateConnector
$connector = new AfasUpdateConnector();

# Authentication
$connector->setEnvironment( 'omgeving' );
$connector->setCredentials( 'gebruikersnaam', 'wachtwoord' );
$connector->setConnector( 'connectorid' );

# Generate the XML
$insertXml = $connector->makeInsert( [] );

# Make the call
$connector->execute($insertXml);
```
`$connector->makeInsert()` accepteert een multi-dimensionale array aan velden en transformeerd deze in XML. Deze velden moet overéénkomen met de gegenereerde XML opbouw uit de XSD.
## Gegevens aanpassen
```
# Instantiate a new AfasUpdateConnector
$connector = new AfasUpdateConnector();

# Authentication
$connector->setEnvironment( 'omgeving' );
$connector->setCredentials( 'gebruikersnaam', 'wachtwoord' );
$connector->setConnector( 'connectorid' );

# Set update selectors
$connector->setUpdateSelector( 'field', 'value' );

# Generate the XML
$updateXml = $connector->makeUpdate( [
	'field' => 'value',
	'nested' => [
		'field' => 'value'
	] );

# Make the call
$connector->execute($updateXml);
```
`$connector->setUpdateSelector()` wordt gebruikt om velden aan te geven waarop geüpdate zal worden. Deze methode accepteerd één waarde per keer op een array aan waardes.
`$connector->makeUpdate()` accepteert een multi-dimensionale array aan velden. Deze velden moet overéénkomen met de gegenereerde XML opbouw uit de XSD.
## Bijlages ophalen
```
# Instantiate a new AfasSubjectConnector
$connector = new AfasSubjectConnector();

# Authentication
$connector->setEnvironment( 'omgeving' );
$connector->setCredentials( 'gebruikersnaam', 'wachtwoord' );

# Set which subject we want to retrieve
$connector->setOption( [ 'subjectID' => $regel[ 'SbId' ] ] );

# Make the call
$connector->execute();
```