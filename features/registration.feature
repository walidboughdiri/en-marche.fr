Feature:
  As a visitor
  In order to access the web site
  I can register

  Scenario: I can register as an adherent
    Given I am on "/adhesion"
    When I fill in the following:
      | adherent_registration[firstName]            | Jean-Pierre         |
      | adherent_registration[lastName]             | DURAND              |
      | adherent_registration[emailAddress][first]  | jp@test.com         |
      | adherent_registration[emailAddress][second] | jp@test.com         |
      | adherent_registration[password]             | testtest            |
      | adherent_registration[address][address]     | 1 rue des alouettes |
      | adherent_registration[address][postalCode]  | 94320               |
      | adherent_registration[address][cityName]    | Thiais              |
      | adherent_registration[birthdate][day]       | 29                  |
      | adherent_registration[birthdate][month]     | 1                   |
      | adherent_registration[birthdate][year]      | 1989                |
      | adherent_registration[gender]               | male                |
      | adherent_registration[phone][number]        | 123456789           |
    And I fill in hidden field "adherent_registration_address_city" with "94320-94073"
    And I fill in hidden field "adherent_registration_address_country" with "FR"
    And I check "Oui, j'adhère à la charte des valeurs, aux statuts et aux règles de fonctionnement de La République En Marche, ainsi qu'aux conditions générales d'utilisation du site"
    And I resolved the captcha
    And I clean the "api_user" queue
    And I press "Je rejoins La République En Marche"
    And the response status code should be 200
    And I should be on "/presque-fini"
    And "api_user" should have 1 message
    And "api_user" should have message below:
      | routing_key  | body                                                                                                                            |
      | user.created | {"uuid":"@string@","country":"FR","zipCode":"94320","emailAddress":"jp@test.com","firstName":"Jean-Pierre","lastName":"Durand"} |
    And I should have 1 email
    And I should have 1 email "AdherentAccountActivationMessage" for "jp@test.com" with payload:
    """
    {
      "FromEmail": "contact@en-marche.fr",
      "FromName": "En Marche !",
      "Subject": "Confirmez votre compte En-Marche.fr",
      "MJ-TemplateID": "292269",
      "MJ-TemplateLanguage": true,
      "Recipients": [
        {
          "Email": "jp@test.com",
            "Name": "Jean-Pierre Durand",
            "Vars": {
              "first_name": "Jean-Pierre",
              "activation_link": "http:\/\/test.enmarche.code\/inscription\/finaliser\/@string@\/@string@"
            }
        }
      ]
    }
    """

    Given I am on "/deconnexion"
    And I am on "/connexion"
    When I fill in the following:
      | E-mail       | jp-fail@test.com |
      | Mot de passe | testtesti        |
    And I press "Connexion"
    Then I should be on "/connexion"
    And I should see "L'adresse e-mail et le mot de passe que vous avez saisis ne correspondent pas."

    Given I am on "/connexion"
    When I fill in the following:
      | E-mail       | jp@test.com |
      | Mot de passe | testtest    |
    And I press "Connexion"
    Then I should see "Pour vous connecter vous devez confirmer votre adhésion. Si vous n'avez pas reçu le mail de validation, vous pouvez cliquer ici pour le recevoir à nouveau."

    When I click on the email link "activation_link"
    Then I should be on "/espace-adherent/accueil"

  Scenario: I can register as a user
    Given I am on "/inscription"
    When I fill in the following:
      | Prénom             | Jean-Pierre |
      | Nom                | DURAND      |
      | E-mail             | jp@test.com |
      | Re-saisir l'e-mail | jp@test.com |
      | Mot de passe       | testtest    |
      | Code postal        | 38000       |
      | Pays               | CH          |
    And I resolved the captcha
    And I clean the "api_user" queue
    And I press "Créer mon compte"
    Then I should be on "/presque-fini"
    And the response status code should be 200
    And "api_user" should have 1 message
    And "api_user" should have message below:
      | routing_key  | body                                                                                                                            |
      | user.created | {"uuid":"@string@","country":"CH","zipCode":"38000","emailAddress":"jp@test.com","firstName":"Jean-Pierre","lastName":"Durand"} |
    And I should have 1 email
    And I should have 1 email "AdherentAccountActivationMessage" for "jp@test.com" with payload:
    """
    {
      "FromEmail": "contact@en-marche.fr",
      "FromName": "En Marche !",
      "Subject": "Confirmez votre compte En-Marche.fr",
      "MJ-TemplateID": "292269",
      "MJ-TemplateLanguage": true,
      "Recipients": [
        {
      "Email": "jp@test.com",
          "Name": "Jean-Pierre Durand",
          "Vars": {
            "first_name": "Jean-Pierre",
            "activation_link": "http:\/\/test.enmarche.code\/inscription\/finaliser\/@string@\/@string@"
          }
        }
      ]
    }
    """

    Given I am on "/connexion"
    When I fill in the following:
      | E-mail       | jp@test.com |
      | Mot de passe | testtest    |
    And I press "Connexion"
    Then I should see "Pour vous connecter vous devez confirmer votre adhésion. Si vous n'avez pas reçu le mail de validation, vous pouvez cliquer ici pour le recevoir à nouveau."

    When I click on the email link "activation_link"
    Then I should be on "/adhesion"
    And the "become_adherent[phone][country]" field should contain "CH"

    When I am on "/adhesion"
    Then I should not see "Bienvenue ! Votre e-mail est confirmé."

    And I fill in the following:
      | become_adherent[address][address]    |  |
      | become_adherent[address][cityName]   |  |
      | become_adherent[address][postalCode] |  |
      | become_adherent[gender]              |  |
      | become_adherent[phone][number]       |  |
      | become_adherent[birthdate][day]      |  |
      | become_adherent[birthdate][month]    |  |
      | become_adherent[birthdate][year]     |  |
    When I press "Je rejoins La République En Marche"
    Then I should see 7 ".form__error" elements
    And I should see "L'adresse est obligatoire."
    And I should see "Veuillez renseigner un code postal."
    And I should see "Veuillez renseigner une ville."
    And I should see "Veuillez renseigner un sexe."
    And I should see "Vous devez spécifier votre date de naissance."
    And I should see "Le numéro de téléphone est obligatoire."
    And I should see "Vous devez accepter la charte."

    Given I fill in hidden field "become_adherent_address_city" with "06000-6088"
    And I fill in the following:
      | become_adherent[address][address]    | 1 rue de l'egalite |
      | become_adherent[address][cityName]   | Nice               |
      | become_adherent[address][postalCode] | 06000              |
      | become_adherent[gender]              | male               |
      | become_adherent[phone][country]      | FR                 |
      | become_adherent[phone][number]       | 0600000000         |
      | become_adherent[birthdate][day]      | 1                  |
      | become_adherent[birthdate][month]    | 1                  |
      | become_adherent[birthdate][year]     | 1980               |
    And I check "Oui, j'adhère à la charte des valeurs, aux statuts et aux règles de fonctionnement de La République En Marche, ainsi qu'aux conditions générales d'utilisation du site"
    And I clean the "api_user" queue
    When I press "Je rejoins La République En Marche"
    Then I should be on "/espace-adherent/accueil"
    And I should see "Votre compte adhérent est maintenant actif."
    And "api_user" should have 1 message
    And "api_user" should have message below:
      | routing_key  | body                                                                                                                            |
      | user.updated | {"uuid":"@string@","country":"CH","zipCode":"06000","emailAddress":"jp@test.com","firstName":"Jean-Pierre","lastName":"Durand"} |
    And I should have 2 emails
    And I should have 1 email "AdherentAccountConfirmationMessage" for "jp@test.com" with payload:
    """
    {
        "FromEmail":"contact@en-marche.fr",
        "FromName":"En Marche !",
        "Subject":"Et maintenant ?",
        "MJ-TemplateID":"54673",
        "MJ-TemplateLanguage":true,
        "Recipients":[
            {
                "Email":"jp@test.com",
                "Name":"Jean-Pierre Durand",
                "Vars":{
                    "adherents_count":1,
                    "committees_count":0,
                    "target_firstname":"Jean-Pierre",
                    "target_lastname":"Durand"
                }
            }
        ]
    }
    """

    Given I am on "/parametres/mon-compte"
    Then the response status code should be 200

    Given I follow "Mes informations personnelles"
    Then I should be on "/parametres/mon-compte/modifier"
    And the "adherent[address][address]" field should contain "1 rue de l'egalite"
    And the "adherent[address][country]" field should contain "CH"
    And the "adherent[phone][country]" field should contain "FR"
    And the "adherent[phone][number]" field should contain "06 00 00 00 00"
    And the "adherent[birthdate][day]" field should contain "1"
    And the "adherent[birthdate][month]" field should contain "1"
    And the "adherent[birthdate][year]" field should contain "1980"

    Given I follow "Notifications"
    Then I should be on "/parametres/mon-compte/preferences-des-emails"
    And the element "Emails En Marche !" should be disabled
    And the element "Emails de vos référents" should be disabled
    And the element "Emails de votre animateur local" should be disabled
    And the element "Être notifié(e) de la création de nouveaux projets citoyens" should be disabled

    When I follow "Modifier mon profil"
    Then the response status code should be 200
    And I should be on "/espace-adherent/mon-profil"

  @javascript
  Scenario: I can become adherent with a foreign country
    Given the following fixtures are loaded:
      | LoadUserData |
    And I am logged as "simple-user@example.ch"
    And I am on "/adhesion"
    And I fill in the following:
      | become_adherent[address][address]    | 32 Zeppelinstrasse |
      | become_adherent[address][postalCode] | 8057               |
      | become_adherent[gender]              | male               |
      | become_adherent[phone][number]       | 06 12 34 56 78     |
      | become_adherent[birthdate][day]      | 1                  |
      | become_adherent[birthdate][month]    | 1                  |
      | become_adherent[birthdate][year]     | 1980               |
    And I click the "field-conditions" element
    When I press "Je rejoins La République En Marche"
    Then I should see "Veuillez renseigner une ville."

    Given I fill in the following:
      | become_adherent[address][cityName] | Zürich |
    When I press "Je rejoins La République En Marche"
    Then I should be on "/espace-adherent/accueil"
    And I should see "Votre compte adhérent est maintenant actif."

  @javascript
  Scenario: I can become adherent with a french address
    Given the following fixtures are loaded:
      | LoadUserData |
    And I am logged as "simple-user@example.ch"
    And I am on "/adhesion"
    And I fill in the following:
      | become_adherent[address][country] | FR                  |
      | become_adherent[address][address] | 1 rue des alouettes |
      | become_adherent[gender]           | male                |
      | become_adherent[phone][number]    | 06 12 34 56 78      |
      | become_adherent[birthdate][day]   | 1                   |
      | become_adherent[birthdate][month] | 1                   |
      | become_adherent[birthdate][year]  | 1980                |
    And I click the "field-conditions" element
    When I press "Je rejoins La République En Marche"
    Then I should be on "/adhesion"
    And I should see "Veuillez renseigner une ville."

    Given I fill in the following:
      | become_adherent[address][postalCode] | 69001 |
    And I wait until I see "Lyon" in the "#become_adherent_address_city" element
    When I press "Je rejoins La République En Marche"
    Then I should be on "/espace-adherent/accueil"
    And I should see "Votre compte adhérent est maintenant actif."

  Scenario: I have great error message when register is misfiled
    Given I am on "/inscription"
    When I fill in the following:
      | Prénom             |                  |
      | Nom                |                  |
      | E-mail             | jp@test.com      |
      | Re-saisir l'e-mail | jp2@test.com     |
      | Mot de passe       | testte           |
      | Code postal        | 0000000000000000 |
      | Pays               | FR               |
    And I press "Créer mon compte"
    Then the response status code should be 200
    And I should see 6 ".form__error" elements
    And I should see "Les adresses email ne correspondent pas."
    And I should see "Votre mot de passe doit comporter au moins 8 caractères."
    And I should see "Vous avez été détecté en tant que robot, pourriez-vous réessayer ?"
    And I should see "Le code postal doit contenir moins de 15 caractères"
    And I should see "Cette valeur ne doit pas être vide."
