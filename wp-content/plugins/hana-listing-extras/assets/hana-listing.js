/**
 * HANA Listing Extras — hana-listing.js
 * Gère : modals, formulaire de contact (AJAX), copie de lien, onglets, créneaux de visite
 */

( function ( $ ) {
    'use strict';

    // ================================================================
    //  UTILITAIRES MODALS
    // ================================================================

    /**
     * Ouvre un modal par son ID
     * @param {string} modalId
     */
    function openModal( modalId ) {
        const modal = document.getElementById( modalId );
        if ( ! modal ) return;
        modal.classList.add( 'is-active' );
        document.body.classList.add( 'hana-modal-open' ); // bloque le scroll
        // Focus trap : mettre le focus sur le premier élément focusable
        const focusable = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if ( focusable.length ) focusable[0].focus();
    }

    /**
     * Ferme un modal par son ID
     * @param {string} modalId
     */
    function closeModal( modalId ) {
        const modal = document.getElementById( modalId );
        if ( ! modal ) return;
        modal.classList.remove( 'is-active' );
        // Retire le lock scroll seulement si aucun autre modal n'est ouvert
        if ( ! document.querySelector( '.hana-modal.is-active' ) ) {
            document.body.classList.remove( 'hana-modal-open' );
        }
    }

    // Fermeture via touche Escape
    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' ) {
            document.querySelectorAll( '.hana-modal.is-active' ).forEach( function ( modal ) {
                closeModal( modal.id );
            } );
        }
    } );

    // Boutons génériques d'ouverture [data-modal-open]
    document.querySelectorAll( '[data-modal-open]' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            openModal( this.dataset.modalOpen );
        } );
    } );

    // Boutons génériques de fermeture [data-modal-close]
    document.querySelectorAll( '[data-modal-close]' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            closeModal( this.dataset.modalClose );
        } );
    } );

    // Overlay click → fermer le modal parent
    document.querySelectorAll( '.hana-modal-overlay' ).forEach( function ( overlay ) {
        overlay.addEventListener( 'click', function () {
            const modal = this.closest( '.hana-modal' );
            if ( modal ) closeModal( modal.id );
        } );
    } );

    // Bouton × dans les modals
    document.querySelectorAll( '.hana-modal-close' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const modal = this.closest( '.hana-modal' );
            if ( modal ) closeModal( modal.id );
        } );
    } );

    // ── Bouton "Partager cette propriété" (sidebar) ──────────────────
    const shareTrigger = document.querySelector( '.hana-btn-share-trigger' );
    if ( shareTrigger ) {
        shareTrigger.addEventListener( 'click', function () {
            openModal( 'hana-share-modal' );
        } );
    }

    // ── Bouton "Contacter le propriétaire" (sidebar) ─────────────────
    const contactTrigger = document.querySelector( '.hana-btn-contact-trigger' );
    if ( contactTrigger ) {
        contactTrigger.addEventListener( 'click', function () {
            openModal( 'hana-contact-modal' );
        } );
    }

    // ── Bouton "Contacter le propriétaire" dans la section principale ─
    // (au cas où il y en aurait un autre dans le DOM)
    document.querySelectorAll( '.hana-btn-contact' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            openModal( 'hana-contact-modal' );
        } );
    } );

    // ================================================================
    //  COPIE DU LIEN
    // ================================================================

    document.querySelectorAll( '.hana-share-btn--copy' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const url = this.dataset.url || window.location.href;

            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                navigator.clipboard.writeText( url ).then( () => {
                    showCopyFeedback( btn );
                } ).catch( () => {
                    fallbackCopy( url, btn );
                } );
            } else {
                fallbackCopy( url, btn );
            }
        } );
    } );

    function showCopyFeedback( btn ) {
        const label = btn.querySelector( 'span' );
        const originalText = label ? label.textContent : '';
        const successText  = ( typeof hanaAjax !== 'undefined' && hanaAjax.strings.copied )
                             ? hanaAjax.strings.copied
                             : 'Lien copié !';

        btn.classList.add( 'hana-share-btn--copied' );
        if ( label ) label.textContent = successText;

        setTimeout( function () {
            btn.classList.remove( 'hana-share-btn--copied' );
            if ( label ) label.textContent = originalText;
        }, 2500 );
    }

    function fallbackCopy( text, btn ) {
        const ta = document.createElement( 'textarea' );
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
        document.body.appendChild( ta );
        ta.select();
        try {
            document.execCommand( 'copy' );
            showCopyFeedback( btn );
        } catch ( err ) {
            console.warn( 'HANA: copie impossible', err );
        }
        document.body.removeChild( ta );
    }

    // ================================================================
    //  ONGLETS + PANNEAUX
    // ================================================================

    const tabs    = document.querySelectorAll( '.hana-tab[data-tab]' );
    const panels  = document.querySelectorAll( '.hana-tab-panel' );

    function activateTab( selectedTab ) {
        // 1. Désactiver tous les onglets
        tabs.forEach( function ( t ) {
            t.classList.remove( 'is-active' );
            t.setAttribute( 'aria-selected', 'false' );
        } );

        // 2. Masquer tous les panneaux
        panels.forEach( function ( p ) {
            p.classList.remove( 'is-active' );
        } );

        // 3. Activer l'onglet cliqué
        selectedTab.classList.add( 'is-active' );
        selectedTab.setAttribute( 'aria-selected', 'true' );

        // 4. Afficher le panneau correspondant
        const targetId = 'hana-panel-' + selectedTab.dataset.tab;
        const targetPanel = document.getElementById( targetId );
        if ( targetPanel ) {
            targetPanel.classList.add( 'is-active' );
        }
    }

    tabs.forEach( function ( tab ) {
        tab.addEventListener( 'click', function () {
            activateTab( this );
        } );
    } );

    // Activer l'onglet "visites" depuis un créneau sidebar
    // (les créneaux sidebar peuvent déclencher le changement d'onglet)
    document.querySelectorAll( '.hana-visit-slot:not(.hana-visit-slot--more)' ).forEach( function ( slot ) {
        slot.addEventListener( 'click', function () {
            const visitesTab = document.querySelector( '.hana-tab[data-tab="visites"]' );
            if ( visitesTab ) activateTab( visitesTab );
        } );
    } );

    // ================================================================
    //  CRÉNEAUX DE VISITE
    // ================================================================

    // Créneaux dans le panneau "Visites" → sélection + pré-remplissage + ouverture modal contact
    document.querySelectorAll( '#hana-panel-visites .hana-visit-slot' ).forEach( function ( slot ) {
        slot.addEventListener( 'click', function () {
            // Sélection exclusive dans le panneau
            document.querySelectorAll( '#hana-panel-visites .hana-visit-slot' ).forEach( function ( s ) {
                s.classList.remove( 'is-selected' );
            } );
            this.classList.add( 'is-selected' );

            // Pré-remplir le formulaire de contact
            const messageField  = document.getElementById( 'hana-message' );
            const subjectSelect = document.getElementById( 'hana-subject' );
            if ( messageField ) {
                messageField.value = 'Bonjour,\n\nJe souhaite visiter ce bien le '
                    + this.textContent.trim()
                    + '.\n\nPourriez-vous confirmer ce créneau ? Merci !';
            }
            if ( subjectSelect ) subjectSelect.value = 'visite';

            // Ouvrir le modal de contact
            openModal( 'hana-contact-modal' );
        } );
    } );

    // Créneaux dans la SIDEBAR → ouvrent directement le modal contact (sans changer d'onglet)
    document.querySelectorAll( '.hana-sidebar-card .hana-visit-slot:not(.hana-visit-slot--more)' ).forEach( function ( slot ) {
        slot.addEventListener( 'click', function () {
            document.querySelectorAll( '.hana-sidebar-card .hana-visit-slot' ).forEach( function ( s ) {
                s.classList.remove( 'is-selected' );
            } );
            this.classList.add( 'is-selected' );

            const messageField  = document.getElementById( 'hana-message' );
            const subjectSelect = document.getElementById( 'hana-subject' );
            if ( messageField ) {
                messageField.value = 'Bonjour,\n\nJe souhaite visiter ce bien le '
                    + this.textContent.trim()
                    + '.\n\nPourriez-vous confirmer ce créneau ? Merci !';
            }
            if ( subjectSelect ) subjectSelect.value = 'visite';

            openModal( 'hana-contact-modal' );
        } );
    } );

    // ================================================================
    //  FORMULAIRE DE CONTACT — AJAX
    // ================================================================

    const contactForm = document.getElementById( 'hana-contact-form' );
    if ( contactForm ) {
        contactForm.addEventListener( 'submit', function ( e ) {
            e.preventDefault();

            const form       = this;
            const submitBtn  = form.querySelector( '.hana-btn-submit' );
            const msgDiv     = form.querySelector( '.hana-form-message' );

            // Validation HTML5 native
            if ( ! form.checkValidity() ) {
                form.reportValidity();
                return;
            }

            // État de chargement
            submitBtn.disabled = true;
            submitBtn.classList.add( 'is-loading' );
            submitBtn.textContent = 'Envoi en cours…';
            clearFormMessage( msgDiv );

            const formData = new FormData( form );
            formData.append( 'action', 'hana_contact_agent' );
            formData.append( 'nonce', hanaAjax.nonce );

            $.ajax( {
                url:        hanaAjax.ajaxUrl,
                type:       'POST',
                data:       formData,
                processData: false,
                contentType: false,
                success: function ( response ) {
                    if ( response.success ) {
                        showFormMessage( msgDiv, response.data.message, 'success' );
                        form.reset();
                        // Fermer le modal après 3 secondes
                        setTimeout( function () {
                            closeModal( 'hana-contact-modal' );
                            clearFormMessage( msgDiv );
                        }, 3000 );
                    } else {
                        showFormMessage( msgDiv, response.data.message || hanaAjax.strings.error, 'error' );
                    }
                },
                error: function () {
                    showFormMessage( msgDiv, hanaAjax.strings.error, 'error' );
                },
                complete: function () {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove( 'is-loading' );
                    submitBtn.textContent = 'Envoyer le message';
                },
            } );
        } );
    }

    function showFormMessage( el, message, type ) {
        if ( ! el ) return;
        el.textContent  = message;
        el.className    = 'hana-form-message hana-form-message--' + type;
        el.style.display = 'block';
    }

    function clearFormMessage( el ) {
        if ( ! el ) return;
        el.textContent   = '';
        el.className     = 'hana-form-message';
        el.style.display = 'none';
    }

    // ================================================================
    //  BOUTON "VOIR LES REVIEWS" — défilement doux
    // ================================================================

    document.querySelectorAll( 'a[href="#hana-reviews-section"]' ).forEach( function ( link ) {
        link.addEventListener( 'click', function ( e ) {
            const target = document.getElementById( 'hana-reviews-section' );
            if ( target ) {
                e.preventDefault();
                target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
            }
        } );
    } );

} )( jQuery );
