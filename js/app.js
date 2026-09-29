document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | VISRS GLOBAL JAVASCRIPT
    |--------------------------------------------------------------------------
    */

    console.log("VISRS JavaScript loaded successfully.");


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION PROTECTION
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("form").forEach(function (form) {

        form.addEventListener("submit", function () {

            const submitButtons = form.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            );

            submitButtons.forEach(function (button) {

                button.disabled = true;

                /*
                 * Do not change delete button text because
                 * VISRS uses dedicated delete confirmation pages.
                 */
                const buttonText = (
                    button.textContent ||
                    button.value ||
                    ""
                ).trim().toLowerCase();

                if (
                    !buttonText.includes("delete") &&
                    !buttonText.includes("remove")
                ) {

                    if (button.tagName.toLowerCase() === "button") {
                        button.textContent = "Processing...";
                    } else {
                        button.value = "Processing...";
                    }

                }

            });

        });

    });


    /*
    |--------------------------------------------------------------------------
    | AUTO DISMISS ALERTS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".auto-dismiss").forEach(function (alert) {

        setTimeout(function () {

            alert.style.opacity = "0";

            setTimeout(function () {
                alert.remove();
            }, 400);

        }, 4000);

    });


    /*
    |--------------------------------------------------------------------------
    | CONFIRM ACTIONS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".confirm-action").forEach(function (element) {

        element.addEventListener("click", function (event) {

            const message =
                element.getAttribute("data-confirm") ||
                "Are you sure you want to continue?";

            if (!window.confirm(message)) {
                event.preventDefault();
            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | AUTO FOCUS
    |--------------------------------------------------------------------------
    */

    const autofocusElement =
        document.querySelector("[data-autofocus]");

    if (autofocusElement) {
        autofocusElement.focus();
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD VISIBILITY TOGGLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-password-toggle]").forEach(
        function (button) {

            button.addEventListener("click", function () {

                const targetId =
                    button.getAttribute("data-password-toggle");

                const input =
                    document.getElementById(targetId);

                if (!input) {
                    return;
                }

                if (input.type === "password") {

                    input.type = "text";
                    button.textContent = "Hide";

                } else {

                    input.type = "password";
                    button.textContent = "Show";

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHARACTER COUNTERS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-character-counter]").forEach(
        function (counter) {

            const targetId =
                counter.getAttribute("data-character-counter");

            const input =
                document.getElementById(targetId);

            if (!input) {
                return;
            }

            function updateCounter() {

                const currentLength =
                    input.value.length;

                const maxLength =
                    input.getAttribute("maxlength");

                if (maxLength) {

                    counter.textContent =
                        currentLength + " / " + maxLength;

                } else {

                    counter.textContent =
                        currentLength + " characters";

                }

            }

            input.addEventListener(
                "input",
                updateCounter
            );

            updateCounter();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE TOGGLE TARGET
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-toggle-target]").forEach(
        function (toggle) {

            toggle.addEventListener("click", function () {

                const targetId =
                    toggle.getAttribute("data-toggle-target");

                const target =
                    document.getElementById(targetId);

                if (!target) {
                    return;
                }

                if (
                    target.style.display === "none" ||
                    getComputedStyle(target).display === "none"
                ) {

                    target.style.display = "";

                } else {

                    target.style.display = "none";

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | TABLE SEARCH
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll("[data-table-search]").forEach(
        function (searchInput) {

            const tableId =
                searchInput.getAttribute("data-table-search");

            const table =
                document.getElementById(tableId);

            if (!table) {
                return;
            }

            const rows =
                table.querySelectorAll("tbody tr");

            searchInput.addEventListener("input", function () {

                const searchTerm =
                    searchInput.value
                        .toLowerCase()
                        .trim();

                rows.forEach(function (row) {

                    const rowText =
                        row.textContent.toLowerCase();

                    if (rowText.includes(searchTerm)) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | VISRS LANGUAGE CHANGER
    |--------------------------------------------------------------------------
    */

    const LANGUAGE_STORAGE_KEY =
        "visrs_language";


    /*
    |--------------------------------------------------------------------------
    | LANGUAGE TRANSLATIONS
    |--------------------------------------------------------------------------
    |
    | These translations are intentionally limited to the VISRS
    | interface. Database information such as:
    |
    | - Vehicle names
    | - Owner names
    | - VIN numbers
    | - Plate numbers
    | - Accident descriptions
    | - Insurance information
    |
    | will NOT be translated.
    |
    |--------------------------------------------------------------------------
    */

    const translations = {

        en: {
            languageName: "English",

            vehicle_information_system: "Vehicle Information System",

            dashboard: "Dashboard",
            vehicle_search: "Vehicle Search",
            vehicles: "Vehicles",
            owners: "Owners",
            ownership_history: "Ownership History",
            insurance: "Insurance",
            accidents: "Accidents",
            marketplace: "Marketplace",
            user_management: "User Management",
            audit_trail: "Audit Trail",
            logout: "Logout",

            login: "Login",

            search: "Search",
            add: "Add",
            edit: "Edit",
            delete: "Delete",
            view: "View",
            save: "Save",
            cancel: "Cancel",
            update: "Update",
            submit: "Submit",
            back: "Back",

            actions: "Actions",
            details: "Details",
            status: "Status",
            date: "Date",
            name: "Name",
            email: "Email",
            phone: "Phone",
            address: "Address",

            vehicle: "Vehicle",
            vehiclesTitle: "Vehicles",
            owner: "Owner",
            ownersTitle: "Owners",

            processing: "Processing...",

            language: "Language",
            selectLanguage: "Select Language",

            yes: "Yes",
            no: "No",

            active: "Active",
            inactive: "Inactive",
            sold: "Sold",
            stolen: "Stolen",

            insuranceTitle: "Insurance",
            accidentsTitle: "Accidents",

            accidentDate: "Accident Date",
            location: "Location",
            description: "Description",
            damageLevel: "Damage Level",
            reportNumber: "Report Number",

            providerName: "Provider Name",
            policyNumber: "Policy Number",
            coverageType: "Coverage Type",
            startDate: "Start Date",
            expiryDate: "Expiry Date",

            firstName: "First Name",
            lastName: "Last Name",
            role: "Role",

            plateNumber: "Plate Number",
            vin: "VIN",
            make: "Make",
            model: "Model",
            year: "Year",
            color: "Color",

            confirmDelete:
                "Are you sure you want to delete this record?",

            noRecords:
                "No records found.",

            allRightsReserved:
                "All rights reserved."
        },


        fr: {
            languageName: "Français",

            vehicle_information_system: "Système d'information sur les véhicules",

            dashboard: "Tableau de bord",
            vehicle_search: "Recherche de véhicules",
            vehicles: "Véhicules",
            owners: "Propriétaires",
            ownership_history: "Historique de propriété",
            insurance: "Assurance",
            accidents: "Accidents",
            marketplace: "Marché",
            user_management: "Gestion des utilisateurs",
            audit_trail: "Piste d'audit",
            logout: "Déconnexion",

            login: "Connexion",

            search: "Rechercher",
            add: "Ajouter",
            edit: "Modifier",
            delete: "Supprimer",
            view: "Voir",
            save: "Enregistrer",
            cancel: "Annuler",
            update: "Mettre à jour",
            submit: "Soumettre",
            back: "Retour",

            actions: "Actions",
            details: "Détails",
            status: "Statut",
            date: "Date",
            name: "Nom",
            email: "E-mail",
            phone: "Téléphone",
            address: "Adresse",

            vehicle: "Véhicule",
            vehiclesTitle: "Véhicules",
            owner: "Propriétaire",
            ownersTitle: "Propriétaires",

            processing: "Traitement...",

            language: "Langue",
            selectLanguage: "Sélectionner la langue",

            yes: "Oui",
            no: "Non",

            active: "Actif",
            inactive: "Inactif",
            sold: "Vendu",
            stolen: "Volé",

            insuranceTitle: "Assurance",
            accidentsTitle: "Accidents",

            accidentDate: "Date de l'accident",
            location: "Lieu",
            description: "Description",
            damageLevel: "Niveau de dommages",
            reportNumber: "Numéro de rapport",

            providerName: "Nom du fournisseur",
            policyNumber: "Numéro de police",
            coverageType: "Type de couverture",
            startDate: "Date de début",
            expiryDate: "Date d'expiration",

            firstName: "Prénom",
            lastName: "Nom de famille",
            role: "Rôle",

            plateNumber: "Numéro d'immatriculation",
            vin: "VIN",
            make: "Marque",
            model: "Modèle",
            year: "Année",
            color: "Couleur",

            confirmDelete:
                "Êtes-vous sûr de vouloir supprimer cet enregistrement ?",

            noRecords:
                "Aucun enregistrement trouvé.",

            allRightsReserved:
                "Tous droits réservés."
        },


        es: {
            languageName: "Español",

            vehicle_information_system: "Sistema de Información de Vehículos",

            dashboard: "Panel",
            vehicle_search: "Búsqueda de vehículos",
            vehicles: "Vehículos",
            owners: "Propietarios",
            ownership_history: "Historial de propiedad",
            insurance: "Seguro",
            accidents: "Accidentes",
            marketplace: "Mercado",
            user_management: "Gestión de usuarios",
            audit_trail: "Registro de auditoría",
            logout: "Cerrar sesión",

            login: "Iniciar sesión",

            search: "Buscar",
            add: "Agregar",
            edit: "Editar",
            delete: "Eliminar",
            view: "Ver",
            save: "Guardar",
            cancel: "Cancelar",
            update: "Actualizar",
            submit: "Enviar",
            back: "Atrás",

            actions: "Acciones",
            details: "Detalles",
            status: "Estado",
            date: "Fecha",
            name: "Nombre",
            email: "Correo electrónico",
            phone: "Teléfono",
            address: "Dirección",

            vehicle: "Vehículo",
            vehiclesTitle: "Vehículos",
            owner: "Propietario",
            ownersTitle: "Propietarios",

            processing: "Procesando...",

            language: "Idioma",
            selectLanguage: "Seleccionar idioma",

            yes: "Sí",
            no: "No",

            active: "Activo",
            inactive: "Inactivo",
            sold: "Vendido",
            stolen: "Robado",

            insuranceTitle: "Seguro",
            accidentsTitle: "Accidentes",

            accidentDate: "Fecha del accidente",
            location: "Ubicación",
            description: "Descripción",
            damageLevel: "Nivel de daños",
            reportNumber: "Número de informe",

            providerName: "Nombre del proveedor",
            policyNumber: "Número de póliza",
            coverageType: "Tipo de cobertura",
            startDate: "Fecha de inicio",
            expiryDate: "Fecha de vencimiento",

            firstName: "Nombre",
            lastName: "Apellido",
            role: "Rol",

            plateNumber: "Número de matrícula",
            vin: "VIN",
            make: "Marca",
            model: "Modelo",
            year: "Año",
            color: "Color",

            confirmDelete:
                "¿Está seguro de que desea eliminar este registro?",

            noRecords:
                "No se encontraron registros.",

            allRightsReserved:
                "Todos los derechos reservados."
        }

    };


    /*
    |--------------------------------------------------------------------------
    | GET SAVED LANGUAGE
    |--------------------------------------------------------------------------
    */

    let currentLanguage = "en";

    try {

        const savedLanguage =
            localStorage.getItem(
                LANGUAGE_STORAGE_KEY
            );

        if (
            savedLanguage &&
            Object.prototype.hasOwnProperty.call(
                translations,
                savedLanguage
            )
        ) {

            currentLanguage = savedLanguage;

        }

    } catch (error) {

        console.warn(
            "VISRS could not read saved language.",
            error
        );

    }


    /*
    |--------------------------------------------------------------------------
    | TRANSLATION FUNCTION
    |--------------------------------------------------------------------------
    */

    function translateText(
        key,
        language = currentLanguage
    ) {

        if (
            !translations[language] ||
            !translations[language][key]
        ) {

            return key;

        }

        return translations[language][key];

    }


    /*
    |--------------------------------------------------------------------------
    | APPLY LANGUAGE
    |--------------------------------------------------------------------------
    */

    function applyLanguage(language) {

        if (!translations[language]) {
            language = "en";
        }

        currentLanguage = language;

        /*
         * Set the HTML language attribute.
         */
        document.documentElement.lang = language;


        /*
         * Translate elements using:
         *
         * data-i18n="vehicles"
         */
        document.querySelectorAll("[data-i18n]").forEach(
            function (element) {

                const key =
                    element.getAttribute("data-i18n");

                if (
                    translations[language] &&
                    translations[language][key]
                ) {

                    element.textContent =
                        translations[language][key];

                }

            }
        );


        /*
         * Translate placeholders using:
         *
         * data-i18n-placeholder="search"
         */
        document.querySelectorAll(
            "[data-i18n-placeholder]"
        ).forEach(function (element) {

            const key =
                element.getAttribute(
                    "data-i18n-placeholder"
                );

            if (
                translations[language] &&
                translations[language][key]
            ) {

                element.setAttribute(
                    "placeholder",
                    translations[language][key]
                );

            }

        });


        /*
         * Translate titles/tooltips using:
         *
         * data-i18n-title="language"
         */
        document.querySelectorAll(
            "[data-i18n-title]"
        ).forEach(function (element) {

            const key =
                element.getAttribute(
                    "data-i18n-title"
                );

            if (
                translations[language] &&
                translations[language][key]
            ) {

                element.setAttribute(
                    "title",
                    translations[language][key]
                );

            }

        });


        /*
         * Update language selector if it exists.
         */
        const languageSelector =
            document.getElementById(
                "visrsLanguageSelector"
            );

        if (languageSelector) {

            languageSelector.value =
                language;

        }


        /*
         * Save selected language.
         */
        try {

            localStorage.setItem(
                LANGUAGE_STORAGE_KEY,
                language
            );

        } catch (error) {

            console.warn(
                "VISRS could not save selected language.",
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | LANGUAGE SELECTOR
    |--------------------------------------------------------------------------
    */

    const languageSelector =
        document.getElementById(
            "visrsLanguageSelector"
        );

    if (languageSelector) {

        languageSelector.addEventListener(
            "change",
            function () {

                applyLanguage(
                    languageSelector.value
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | APPLY SAVED LANGUAGE
    |--------------------------------------------------------------------------
    */

    applyLanguage(currentLanguage);


    /*
    |--------------------------------------------------------------------------
    | VISRS LANGUAGE READY
    |--------------------------------------------------------------------------
    */

    console.log(
        "VISRS language changer loaded."
    );

});